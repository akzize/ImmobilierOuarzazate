<?php

namespace App\Filament\Resources\Properties\Actions;

use App\Enums\PropertyStatus;
use App\Models\Property;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Listing actions shared by the properties table and the View page.
 */
class PropertyActions
{
    /**
     * One quick action per status; the listing's current status is hidden.
     *
     * @return array<Action>
     */
    public static function changeStatus(): array
    {
        return array_map(self::changeStatusTo(...), PropertyStatus::cases());
    }

    public static function bulkChangeStatus(): BulkAction
    {
        return BulkAction::make('changeStatus')
            ->label(__('Change status'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->schema([
                Select::make('status')
                    ->options(PropertyStatus::class)
                    ->required(),
            ])
            ->action(function (BulkAction $action, Collection $records, array $data): void {
                $status = $data['status'];

                $records->each(function (Property $record) use ($action, $status): void {
                    if ($status === PropertyStatus::Published && ! $record->canBePublished()) {
                        $action->reportBulkProcessingFailure(
                            'missing_photos',
                            message: fn (int $failureCount): string => trans_choice(
                                ':count listing has no photo and was not published.|:count listings have no photos and were not published.',
                                $failureCount,
                            ),
                        );

                        return;
                    }

                    $record->update(['status' => $status]);
                });
            })
            ->successNotificationTitle(__('Status updated'))
            ->failureNotificationTitle(fn (int $successCount, int $totalCount): string => __(':success of :total listings updated', [
                'success' => $successCount,
                'total' => $totalCount,
            ]))
            ->deselectRecordsAfterCompletion();
    }

    public static function openOnWebsite(): Action
    {
        return Action::make('openOnWebsite')
            ->label(__('Open on website'))
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (Property $record): ?string => $record->publicUrl(), shouldOpenInNewTab: true)
            ->visible(fn (Property $record): bool => $record->publicUrl() !== null);
    }

    private static function changeStatusTo(PropertyStatus $status): Action
    {
        return Action::make(Str::camel('mark_as_'.$status->value))
            ->label($status->getActionLabel())
            ->icon($status->getIcon())
            ->color($status->getColor())
            ->requiresConfirmation()
            ->visible(fn (Property $record): bool => $record->status !== $status)
            ->action(function (Action $action, Property $record) use ($status): void {
                if ($status === PropertyStatus::Published && ! $record->canBePublished()) {
                    $action->failureNotificationTitle(__('Add at least one photo before publishing.'));
                    $action->failure();

                    return;
                }

                $record->update(['status' => $status]);
            })
            ->successNotificationTitle(__('Listing marked as :status', ['status' => $status->getLabel()]));
    }
}
