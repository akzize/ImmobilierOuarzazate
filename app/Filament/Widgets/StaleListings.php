<?php

namespace App\Filament\Widgets;

use App\Enums\PropertyStatus;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listings published for a long time that nobody has asked about lately: candidates for a price cut or new photos.
 */
class StaleListings extends TableWidget
{
    /**
     * A listing with no inquiry for this long counts as quiet.
     */
    public const int QUIET_DAYS = 30;

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Stale listings'))
            ->description(__('Published over :stale days ago, no inquiry in the last :quiet days.', [
                'stale' => Property::STALE_AFTER_DAYS,
                'quiet' => self::QUIET_DAYS,
            ]))
            ->query(fn (): Builder => Property::query()
                ->where('status', PropertyStatus::Published)
                ->where('published_at', '<=', now()->subDays(Property::STALE_AFTER_DAYS))
                ->whereDoesntHave('inquiries', fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(self::QUIET_DAYS)))
                ->withMax('inquiries', 'created_at'))
            ->defaultSort('published_at')
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Property $record): string => PropertyResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference'),
                TextColumn::make('price')
                    ->money('MAD', decimalPlaces: 0),
                TextColumn::make('days_on_market')
                    ->label(__('Days on market'))
                    ->state(fn (Property $record): ?int => $record->daysOnMarket()),
                TextColumn::make('inquiries_max_created_at')
                    ->label(__('Last inquiry'))
                    ->since()
                    ->placeholder(__('Never')),
            ])
            ->emptyStateHeading(__('No stale listings'))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
