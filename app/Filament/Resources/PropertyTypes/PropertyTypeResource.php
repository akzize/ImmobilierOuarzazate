<?php

namespace App\Filament\Resources\PropertyTypes;

use App\Enums\NavigationGroup;
use App\Enums\PropertyGroup;
use App\Filament\Resources\PropertyTypes\Pages\ManagePropertyTypes;
use App\Models\PropertyType;
use App\Rules\ArabicText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Property types offered in the listing form, in the order set here.
 * The code is fixed once created: the website filters on it and the listing form relies on some codes (RIAD, KSB, FARM, HOT).
 * The group decides which fields a listing has, so it is locked once listings use the type. A type in use cannot be deleted.
 */
class PropertyTypeResource extends Resource
{
    protected static ?string $model = PropertyType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'label_en';

    public static function getModelLabel(): string
    {
        return __('property type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('property types');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(10)
                    ->regex('/^[A-Z][A-Z0-9]*$/')
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->helperText(__('Short uppercase code used by the website, e.g. VIL. It cannot be changed later.')),
                Select::make('group')
                    ->options(PropertyGroup::class)
                    ->required()
                    ->disabled(fn (?PropertyType $record): bool => $record?->properties()->exists() ?? false)
                    ->helperText(__('Decides which fields listings of this type have. Locked once listings use the type.')),
                TextInput::make('label_en')
                    ->label(__('Label (English)'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('label_ar')
                    ->label(__('Label (Arabic)'))
                    ->required()
                    ->maxLength(255)
                    ->rule(new ArabicText)
                    ->extraInputAttributes(['dir' => 'rtl']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label_en')
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('label_en')
                    ->label(__('Label'))
                    ->description(fn (PropertyType $record): string => $record->label_ar)
                    ->searchable(['label_en', 'label_ar']),
                TextColumn::make('group')
                    ->badge(),
                TextColumn::make('properties_count')
                    ->label(__('Listings'))
                    ->counts('properties'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (PropertyType $record): bool => $record->properties_count > 0),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePropertyTypes::route('/'),
        ];
    }
}
