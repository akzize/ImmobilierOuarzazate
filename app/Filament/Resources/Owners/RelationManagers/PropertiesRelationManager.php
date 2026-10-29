<?php

namespace App\Filament\Resources\Owners\RelationManagers;

use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The owner's listings, read-only: each row opens the listing in the Properties resource.
 */
class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Property $record): string => PropertyResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('title_ar')
                    ->label(__('Title'))
                    ->description(fn (Property $record): ?string => $record->title_en)
                    ->searchable(['title_ar', 'title_en'])
                    ->limit(40),
                TextColumn::make('transaction')
                    ->badge(),
                TextColumn::make('price')
                    ->money('MAD', decimalPlaces: 0),
                TextColumn::make('status')
                    ->badge(),
            ]);
    }
}
