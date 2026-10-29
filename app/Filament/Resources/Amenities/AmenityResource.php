<?php

namespace App\Filament\Resources\Amenities;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Amenities\Pages\ManageAmenities;
use App\Models\Amenity;
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
 * Amenities offered in the listing form. The code is fixed once created because the website relies on it.
 * An amenity used by listings cannot be deleted.
 */
class AmenityResource extends Resource
{
    protected static ?string $model = Amenity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'label_en';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->regex('/^[a-z][a-z0-9_]*$/')
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->helperText(__('Lowercase code used by the website, e.g. solar_water_heater. It cannot be changed later.'))
                    ->columnSpanFull(),
                TextInput::make('label_en')
                    ->label(__('Label (English)'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('label_ar')
                    ->label(__('Label (Arabic)'))
                    ->required()
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'rtl']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label_en')
            ->defaultSort('label_en')
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('label_en')
                    ->label(__('Label'))
                    ->description(fn (Amenity $record): string => $record->label_ar)
                    ->searchable(['label_en', 'label_ar'])
                    ->sortable(),
                TextColumn::make('properties_count')
                    ->label(__('Listings'))
                    ->counts('properties')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Amenity $record): bool => $record->properties_count > 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAmenities::route('/'),
        ];
    }
}
