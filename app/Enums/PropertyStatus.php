<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PropertyStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Reserved = 'reserved';
    case SoldOrRented = 'sold_or_rented';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => __('Published'),
            self::Reserved => __('Reserved'),
            self::SoldOrRented => __('Sold / Rented'),
            self::Archived => __('Archived'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Reserved => 'warning',
            self::SoldOrRented => 'info',
            self::Archived => 'danger',
        };
    }
}
