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
 * Published listings missing something that helps them sell: an English translation, enough photos or a map location.
 */
class ListingsToFix extends TableWidget
{
    public const int RECOMMENDED_PHOTOS = 5;

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Listings to improve'))
            ->query(fn (): Builder => Property::query()
                ->where('status', PropertyStatus::Published)
                ->withCount('images')
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('title_en')
                    ->orWhere('title_en', '')
                    ->orWhereNull('description_en')
                    ->orWhere('description_en', '')
                    ->orWhereNull('latitude')
                    ->orHas('images', '<', self::RECOMMENDED_PHOTOS)))
            ->defaultSort('published_at')
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Property $record): string => PropertyResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('reference'),
                TextColumn::make('title_ar')
                    ->label(__('Title'))
                    ->limit(30),
                TextColumn::make('problems')
                    ->label(__('Missing'))
                    ->state(fn (Property $record): array => self::problems($record))
                    ->badge()
                    ->color('warning'),
            ])
            ->emptyStateHeading(__('Every published listing is complete'))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }

    /**
     * @return list<string>
     */
    private static function problems(Property $record): array
    {
        return array_keys(array_filter([
            __('English translation') => blank($record->title_en) || blank($record->description_en),
            __('Photos (:count+)', ['count' => self::RECOMMENDED_PHOTOS]) => $record->images_count < self::RECOMMENDED_PHOTOS,
            __('Map location') => $record->latitude === null,
        ]));
    }
}
