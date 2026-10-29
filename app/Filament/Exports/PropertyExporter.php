<?php

namespace App\Filament\Exports;

use App\Models\Property;
use Closure;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

/**
 * CSV / XLSX export of listings, including private columns (owner, address, notes): the panel is admin only.
 */
class PropertyExporter extends Exporter
{
    protected static ?string $model = Property::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference'),
            ExportColumn::make('title_ar')
                ->label(__('Title (Arabic)')),
            ExportColumn::make('title_en')
                ->label(__('Title (English)')),
            ExportColumn::make('propertyType.label_en')
                ->label(__('Type')),
            ExportColumn::make('city.name_en')
                ->label(__('City')),
            ExportColumn::make('transaction')
                ->formatStateUsing(self::enumLabel()),
            ExportColumn::make('status')
                ->formatStateUsing(self::enumLabel()),
            ExportColumn::make('featured')
                ->formatStateUsing(self::yesNo()),
            ExportColumn::make('price')
                ->label(__('Price (MAD)')),
            ExportColumn::make('price_negotiable')
                ->label(__('Price negotiable'))
                ->formatStateUsing(self::yesNo()),
            ExportColumn::make('surface_m2')
                ->label(__('Surface (m²)')),
            ExportColumn::make('built_area_m2')
                ->label(__('Built area (m²)')),
            ExportColumn::make('bedrooms'),
            ExportColumn::make('bathrooms'),
            ExportColumn::make('legal_title')
                ->label(__('Legal title'))
                ->formatStateUsing(self::enumLabel()),
            ExportColumn::make('latitude'),
            ExportColumn::make('longitude'),
            ExportColumn::make('video_url')
                ->label(__('Video URL')),
            ExportColumn::make('owner.name_en')
                ->label(__('Owner')),
            ExportColumn::make('owner.phone')
                ->label(__('Owner phone')),
            ExportColumn::make('address'),
            ExportColumn::make('internal_notes')
                ->label(__('Internal notes')),
            ExportColumn::make('closed_price')
                ->label(__('Final price')),
            ExportColumn::make('closed_at')
                ->label(__('Closing date')),
            ExportColumn::make('published_at')
                ->label(__('First published')),
            ExportColumn::make('created_at')
                ->label(__('Created')),
            ExportColumn::make('description_ar')
                ->label(__('Description (Arabic)'))
                ->formatStateUsing(self::plainText())
                ->enabledByDefault(false),
            ExportColumn::make('description_en')
                ->label(__('Description (English)'))
                ->formatStateUsing(self::plainText())
                ->enabledByDefault(false),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your property export has completed and '.Str::of('row')->counted($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Str::of('row')->counted($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    private static function enumLabel(): Closure
    {
        return fn (?HasLabel $state): ?string => $state?->getLabel();
    }

    private static function yesNo(): Closure
    {
        return fn (bool $state): string => $state ? __('Yes') : __('No');
    }

    /**
     * Descriptions are rich text; spreadsheets get the text without the HTML tags.
     */
    private static function plainText(): Closure
    {
        return fn (?string $state): ?string => $state === null ? null : trim(html_entity_decode(strip_tags($state)));
    }
}
