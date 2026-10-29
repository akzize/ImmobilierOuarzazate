<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum PropertyStatus: string implements HasColor, HasIcon, HasLabel
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

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Published => Heroicon::OutlinedGlobeAlt,
            self::Reserved => Heroicon::OutlinedClock,
            self::SoldOrRented => Heroicon::OutlinedCheckBadge,
            self::Archived => Heroicon::OutlinedArchiveBox,
        };
    }

    /**
     * Published or reserved: the listing is still being marketed.
     */
    public function isOnMarket(): bool
    {
        return in_array($this, [self::Published, self::Reserved], true);
    }

    /**
     * Label of the button that moves a listing to this status.
     */
    public function getActionLabel(): string
    {
        return match ($this) {
            self::Draft => __('Move back to drafts'),
            self::Published => __('Publish'),
            self::Reserved => __('Mark as reserved'),
            self::SoldOrRented => __('Mark as sold / rented'),
            self::Archived => __('Archive'),
        };
    }
}
