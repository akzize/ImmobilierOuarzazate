<?php

namespace App\Filament\Forms\Components;

use App\Rules\GeoJsonPolygon;
use Filament\Forms\Components\Field;

/**
 * Map for locating a listing: click to drop the pin (the sibling `latitude` / `longitude` fields),
 * and/or draw the outline of the plot. The field's own state is the outline, a GeoJSON Polygon geometry.
 * The JavaScript lives in resources/js/filament/map-picker.js (build it with `npm run build:filament`).
 */
class MapPicker extends Field
{
    /**
     * Ouarzazate city centre, shown when the listing has no location yet.
     *
     * @var array{0: float, 1: float}
     */
    public const array DEFAULT_CENTER = [30.9335, -6.937];

    protected string $view = 'filament.forms.components.map-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(new GeoJsonPolygon);

        // Keep only the geometry itself, whatever else the browser sent.
        $this->dehydrateStateUsing(fn (?array $state): ?array => $state === null ? null : [
            'type' => $state['type'],
            'coordinates' => $state['coordinates'],
        ]);
    }

    public function getLatitudeStatePath(): string
    {
        return $this->resolveRelativeStatePath('latitude');
    }

    public function getLongitudeStatePath(): string
    {
        return $this->resolveRelativeStatePath('longitude');
    }

    /**
     * Texts used by the map's JavaScript, shared with MapEntry.
     *
     * @return array{satellite: string, streets: string, area: string}
     */
    public static function labels(): array
    {
        return [
            'satellite' => __('Satellite'),
            'streets' => __('Map'),
            'area' => __('Drawn area'),
        ];
    }
}
