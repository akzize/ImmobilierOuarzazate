<?php

namespace App\Filament\Resources\Properties\Tables;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Filament\Exports\PropertyExporter;
use App\Filament\Resources\Properties\Actions\PropertyActions;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
use App\Models\PropertyImage;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('coverImage.path')
                    ->label('')
                    ->disk(PropertyImage::DISK)
                    ->square(),
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title_ar')
                    ->label(__('Title'))
                    ->description(fn ($record): ?string => $record->title_en)
                    ->searchable(['title_ar', 'title_en'])
                    ->limit(40),
                TextColumn::make('propertyType.label_en')
                    ->label(__('Type')),
                TextColumn::make('city.name_en')
                    ->label(__('City'))
                    ->toggleable(),
                TextColumn::make('transaction')
                    ->badge(),
                TextColumn::make('price')
                    ->money('MAD', decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('days_on_market')
                    ->label(__('Days on market'))
                    ->state(fn (Property $record): ?int => $record->daysOnMarket())
                    ->color(fn (?int $state): ?string => $state > Property::STALE_AFTER_DAYS ? 'warning' : null)
                    ->tooltip(fn (?int $state): ?string => $state > Property::STALE_AFTER_DAYS ? __('On the market for more than :days days', ['days' => Property::STALE_AFTER_DAYS]) : null)
                    ->placeholder('—')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('published_at', $direction === 'asc' ? 'desc' : 'asc'))
                    ->toggleable(),
                TextColumn::make('inquiries_count')
                    ->label(__('Inquiries'))
                    ->counts('inquiries')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'primary' : 'gray')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('images_count')
                    ->label(__('Photos'))
                    ->counts('images')
                    ->color(fn (int $state): ?string => $state === 0 ? 'danger' : null)
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('featured')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('owner.name_en')
                    ->label(__('Owner'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('transaction')
                    ->options(TransactionType::class),
                SelectFilter::make('property_type_id')
                    ->label(__('Type'))
                    ->relationship('propertyType', 'label_en')
                    ->preload(),
                SelectFilter::make('city_id')
                    ->label(__('City'))
                    ->relationship('city', 'name_en')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('featured'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    ActionGroup::make(PropertyActions::changeStatus())->dropdown(false),
                    self::replicateAction(),
                    PropertyActions::openOnWebsite(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(PropertyExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    PropertyActions::bulkChangeStatus(),
                    ExportBulkAction::make()
                        ->exporter(PropertyExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Copies the listing as a new draft with its amenities. Photos are not copied: two listings must never share an image file,
     * because deleting one listing deletes its files.
     */
    private static function replicateAction(): ReplicateAction
    {
        return ReplicateAction::make()
            ->label(__('Duplicate'))
            ->modalDescription(__('The copy is saved as a draft with a new reference. Photos are not copied.'))
            // The *_count values are loaded by the table's count columns, not real columns.
            ->excludeAttributes(['reference', 'slug', 'published_at', 'inquiries_count', 'images_count'])
            ->beforeReplicaSaved(function (Property $replica): void {
                $replica->status = PropertyStatus::Draft;
                $replica->featured = false;
            })
            ->after(function (Property $record, Property $replica): void {
                $replica->amenities()->sync($record->amenities);
            })
            ->successRedirectUrl(fn (Property $replica): string => PropertyResource::getUrl('edit', ['record' => $replica]));
    }
}
