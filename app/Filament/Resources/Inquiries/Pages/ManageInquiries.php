<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ManageInquiries extends ManageRecords
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Log inquiry')),
        ];
    }

    /**
     * One tab per status, with the number of inquiries in each (counted in a single query).
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $countsByStatus = self::countsByStatus();

        $statusTabs = collect(InquiryStatus::cases())
            ->mapWithKeys(fn (InquiryStatus $status): array => [
                $status->value => Tab::make($status->getLabel())
                    ->icon($status->getIcon())
                    ->badge($countsByStatus->get($status->value) ?: null)
                    ->badgeColor($status->getColor())
                    ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status)),
            ])
            ->all();

        return [
            'all' => Tab::make(__('All'))
                ->badge($countsByStatus->sum() ?: null),
            ...$statusTabs,
        ];
    }

    /**
     * @return Collection<string, int>
     */
    private static function countsByStatus(): Collection
    {
        return Inquiry::query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (int|string $count): int => (int) $count);
    }
}
