<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Enums\TransactionType;
use App\Filament\Infolists\Components\MapEntry;
use App\Models\Property;
use App\Models\PropertyImage;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Str;

/**
 * Read-only summary on the View page: content on the left, listing facts and private information on the right.
 */
class PropertyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    self::photosSection(),
                    self::contentTabs(),
                    self::characteristicsSection(),
                    self::locationSection(),
                ])->columnSpan(2),
                Group::make([
                    self::listingSection(),
                    self::privateSection(),
                ]),
            ]);
    }

    /**
     * Turns the `details` JSON into "Label: value" lines. Labels come from the keys (e.g. `living_rooms` → "Living Rooms"),
     * which keeps this list in step with the form without repeating every field.
     *
     * @param  array<string, mixed>  $details
     * @return list<string>
     */
    public static function detailLines(array $details): array
    {
        return collect($details)
            ->reject(fn (mixed $value): bool => $value === null || $value === '' || $value === [])
            ->map(fn (mixed $value, string $key): string => Str::headline($key).': '.match (true) {
                is_bool($value) => $value ? __('Yes') : __('No'),
                is_array($value) => collect($value)->map(fn (string $item): string => Str::headline($item))->implode(', '),
                is_string($value) && preg_match('/^[a-z]+(_[a-z]+)*$/', $value) === 1 => Str::headline($value),
                default => (string) $value,
            })
            ->values()
            ->all();
    }

    private static function photosSection(): Section
    {
        return Section::make(__('Photos'))
            ->schema([
                ImageEntry::make('images.path')
                    ->hiddenLabel()
                    ->disk(PropertyImage::DISK)
                    ->imageHeight(140)
                    ->placeholder(__('No photos yet')),
            ]);
    }

    private static function contentTabs(): Tabs
    {
        return Tabs::make()
            ->tabs([
                Tab::make(__('Arabic'))
                    ->schema([
                        TextEntry::make('title_ar')
                            ->label(__('Title'))
                            ->weight(FontWeight::Bold)
                            ->extraAttributes(['dir' => 'rtl']),
                        TextEntry::make('description_ar')
                            ->label(__('Description'))
                            ->html()
                            ->extraAttributes(['dir' => 'rtl']),
                    ]),
                Tab::make(__('English'))
                    ->schema([
                        TextEntry::make('title_en')
                            ->label(__('Title'))
                            ->weight(FontWeight::Bold)
                            ->placeholder(__('Not translated yet')),
                        TextEntry::make('description_en')
                            ->label(__('Description'))
                            ->html()
                            ->placeholder(__('Not translated yet')),
                    ]),
            ]);
    }

    private static function characteristicsSection(): Section
    {
        return Section::make(__('Characteristics'))
            ->columns(3)
            ->schema([
                TextEntry::make('surface_m2')
                    ->label(__('Surface'))
                    ->suffix(' m²'),
                TextEntry::make('built_area_m2')
                    ->label(__('Built area'))
                    ->suffix(' m²')
                    ->placeholder('—'),
                TextEntry::make('legal_title')
                    ->label(__('Legal title'))
                    ->placeholder('—'),
                TextEntry::make('bedrooms')
                    ->placeholder('—'),
                TextEntry::make('bathrooms')
                    ->placeholder('—'),
                TextEntry::make('amenities.label_en')
                    ->label(__('Amenities'))
                    ->badge()
                    ->placeholder(__('None'))
                    ->columnSpanFull(),
                TextEntry::make('details')
                    ->label(__('Type-specific details'))
                    ->state(fn (Property $record): array => self::detailLines($record->details ?? []))
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder(__('None'))
                    ->columnSpanFull(),
            ]);
    }

    private static function locationSection(): Section
    {
        return Section::make(__('Location on map'))
            ->visible(fn (Property $record): bool => self::hasLocation($record) || $record->boundary !== null)
            ->schema([
                MapEntry::make('boundary')
                    ->hiddenLabel(),
            ]);
    }

    private static function listingSection(): Section
    {
        return Section::make(__('Listing'))
            ->schema([
                TextEntry::make('reference')
                    ->weight(FontWeight::Bold)
                    ->copyable(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('transaction')
                    ->badge(),
                TextEntry::make('propertyType.label_en')
                    ->label(__('Type')),
                TextEntry::make('city.name_en')
                    ->label(__('City')),
                TextEntry::make('price')
                    ->money('MAD', decimalPlaces: 0)
                    ->suffix(fn (Property $record): ?string => match ($record->transaction) {
                        TransactionType::Rent => ' '.__('/ month'),
                        TransactionType::SeasonalRent => ' '.__('/ night'),
                        default => null,
                    }),
                IconEntry::make('price_negotiable')
                    ->label(__('Price negotiable'))
                    ->boolean(),
                IconEntry::make('featured')
                    ->boolean(),
                TextEntry::make('map')
                    ->label(__('GPS location'))
                    ->state(fn (Property $record): ?string => self::hasLocation($record) ? "{$record->latitude}, {$record->longitude}" : null)
                    ->url(fn (Property $record): ?string => self::hasLocation($record) ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}" : null)
                    ->openUrlInNewTab()
                    ->placeholder('—'),
                TextEntry::make('video_url')
                    ->label(__('Video'))
                    ->url(fn (?string $state): ?string => $state)
                    ->openUrlInNewTab()
                    ->placeholder('—'),
                TextEntry::make('published_at')
                    ->label(__('First published'))
                    ->dateTime()
                    ->placeholder(__('Not published yet')),
                TextEntry::make('updated_at')
                    ->label(__('Last updated'))
                    ->since()
                    ->dateTimeTooltip(),
            ]);
    }

    private static function privateSection(): Section
    {
        return Section::make(__('Private information'))
            ->description(__('Internal only. Never shown on the public website.'))
            ->icon('heroicon-o-lock-closed')
            ->schema([
                TextEntry::make('owner.name_en')
                    ->label(__('Owner'))
                    ->placeholder('—'),
                TextEntry::make('owner.phone')
                    ->label(__('Owner phone'))
                    ->url(fn (?string $state): ?string => $state === null ? null : "tel:{$state}")
                    ->placeholder('—'),
                TextEntry::make('address')
                    ->placeholder('—'),
                TextEntry::make('internal_notes')
                    ->label(__('Internal notes'))
                    ->placeholder('—'),
            ]);
    }

    private static function hasLocation(Property $record): bool
    {
        return $record->latitude !== null && $record->longitude !== null;
    }
}
