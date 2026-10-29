<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Language: string implements HasLabel
{
    case Arabic = 'ar';
    case English = 'en';

    public function getLabel(): string
    {
        return match ($this) {
            self::Arabic => __('Arabic'),
            self::English => __('English'),
        };
    }

    /**
     * The language's name written in that language, for the admin language switch.
     */
    public function getNativeName(): string
    {
        return match ($this) {
            self::Arabic => 'العربية',
            self::English => 'English',
        };
    }
}
