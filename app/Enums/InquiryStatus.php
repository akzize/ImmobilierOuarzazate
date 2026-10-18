<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InquiryStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Contacted = 'contacted';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Contacted => __('Contacted'),
            self::Closed => __('Closed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Contacted => 'info',
            self::Closed => 'gray',
        };
    }
}
