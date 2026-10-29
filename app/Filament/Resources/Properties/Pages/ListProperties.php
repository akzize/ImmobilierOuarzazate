<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Enums\PropertyStatus;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * One tab per status, with the number of listings in each (counted in a single query).
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $countsByStatus = self::countsByStatus();

        $statusTabs = collect(PropertyStatus::cases())
            ->mapWithKeys(fn (PropertyStatus $status): array => [
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
        return Property::query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (int|string $count): int => (int) $count);
    }
}
