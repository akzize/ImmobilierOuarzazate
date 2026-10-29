<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A GeoJSON Polygon geometry as drawn on the map: one closed ring (no holes) of [longitude, latitude] positions.
 */
class GeoJsonPolygon implements ValidationRule
{
    public const int MAX_POSITIONS = 500;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! $this->isPolygon($value)) {
            $fail(__('The outline drawn on the map is not a valid shape.'));
        }
    }

    private function isPolygon(mixed $value): bool
    {
        if (! is_array($value) || ($value['type'] ?? null) !== 'Polygon') {
            return false;
        }

        $rings = $value['coordinates'] ?? null;

        if (! is_array($rings) || count($rings) !== 1 || ! is_array($rings[0] ?? null) || ! array_is_list($rings[0])) {
            return false;
        }

        $ring = $rings[0];

        // A closed ring repeats its first position at the end, so a triangle has 4 positions.
        if (count($ring) < 4 || count($ring) > self::MAX_POSITIONS || $ring[0] !== end($ring)) {
            return false;
        }

        foreach ($ring as $position) {
            if (! $this->isPosition($position)) {
                return false;
            }
        }

        return true;
    }

    private function isPosition(mixed $position): bool
    {
        if (! is_array($position) || ! array_is_list($position) || count($position) !== 2) {
            return false;
        }

        [$longitude, $latitude] = $position;

        return $this->isNumber($longitude)
            && $this->isNumber($latitude)
            && abs($longitude) <= 180
            && abs($latitude) <= 90;
    }

    private function isNumber(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }
}
