<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Admin sidebar groups, in display order.
 */
enum NavigationGroup implements HasLabel
{
    case Listings;
    case Leads;
    case Settings;

    public function getLabel(): string
    {
        return match ($this) {
            self::Listings => __('Listings'),
            self::Leads => __('Leads'),
            self::Settings => __('Settings'),
        };
    }
}
