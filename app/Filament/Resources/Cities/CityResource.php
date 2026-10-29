<?php

namespace App\Filament\Resources\Cities;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Cities\Pages\ManageCities;
use App\Models\City;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Cities offered in the listing form. A city used by listings cannot be deleted.
 */
class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name_en')
                    ->label(__('Name (English)'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->required()
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'rtl']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_en')
            ->defaultSort('name_en')
            ->columns([
                TextColumn::make('name_en')
                    ->label(__('Name (English)'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->searchable(),
                TextColumn::make('properties_count')
                    ->label(__('Listings'))
                    ->counts('properties')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (City $record): bool => $record->properties_count > 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCities::route('/'),
        ];
    }
}
