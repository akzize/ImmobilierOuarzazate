<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Decides which type-specific fields a property type uses.
 */
enum PropertyGroup: string implements HasLabel
{
    case Built = 'built';
    case Land = 'land';
    case Commercial = 'commercial';

    public function getLabel(): string
    {
        return match ($this) {
            self::Built => __('Built'),
            self::Land => __('Land'),
            self::Commercial => __('Commercial'),
        };
    }
}
