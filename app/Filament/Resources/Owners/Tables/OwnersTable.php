<?php

namespace App\Filament\Resources\Owners\Tables;

use App\Models\Owner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OwnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name_en')
            ->columns([
                TextColumn::make('name_en')
                    ->label(__('Name'))
                    ->description(fn (Owner $record): ?string => $record->name_ar)
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('properties_count')
                    ->label(__('Listings'))
                    ->counts('properties')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Added'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(__('Their listings are kept, without an owner.')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription(__('Their listings are kept, without an owner.')),
                ]),
            ]);
    }
}
