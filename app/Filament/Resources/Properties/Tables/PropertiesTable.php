<?php

namespace App\Filament\Resources\Properties\Tables;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Models\PropertyImage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

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
                IconColumn::make('featured')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PropertyStatus::class)
                    ->multiple(),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
