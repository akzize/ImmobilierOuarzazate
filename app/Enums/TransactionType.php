<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TransactionType: string implements HasLabel
{
    case Sale = 'sale';
    case Rent = 'rent';
    case SeasonalRent = 'seasonal_rent';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sale => __('Sale'),
            self::Rent => __('Rent'),
            self::SeasonalRent => __('Seasonal rent'),
        };
    }
}
