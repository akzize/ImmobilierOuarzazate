<?php

namespace App\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;

/**
 * Read-only version of MapPicker: the record's pin (`latitude` / `longitude`) and its outline (this entry's state).
 */
class MapEntry extends Entry
{
    protected string $view = 'filament.infolists.components.map-entry';

    public function getLatitude(): ?float
    {
        $latitude = $this->getRecord()?->getAttribute('latitude');

        return $latitude === null ? null : (float) $latitude;
    }

    public function getLongitude(): ?float
    {
        $longitude = $this->getRecord()?->getAttribute('longitude');

        return $longitude === null ? null : (float) $longitude;
    }
}
