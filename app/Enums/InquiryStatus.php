<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum InquiryStatus: string implements HasColor, HasIcon, HasLabel
{
    case New = 'new';
    case Contacted = 'contacted';
    case VisitScheduled = 'visit_scheduled';
    case Won = 'won';
    case Lost = 'lost';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Contacted => __('Contacted'),
            self::VisitScheduled => __('Visit scheduled'),
            self::Won => __('Won'),
            self::Lost => __('Lost'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Contacted => 'info',
            self::VisitScheduled => 'primary',
            self::Won => 'success',
            self::Lost => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::New => Heroicon::OutlinedInboxArrowDown,
            self::Contacted => Heroicon::OutlinedChatBubbleLeftRight,
            self::VisitScheduled => Heroicon::OutlinedCalendarDays,
            self::Won => Heroicon::OutlinedTrophy,
            self::Lost => Heroicon::OutlinedXCircle,
        };
    }

    /**
     * Won or lost: the lead is finished.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Won, self::Lost], true);
    }

    /**
     * Label of the button that moves an inquiry to this status.
     */
    public function getActionLabel(): string
    {
        return match ($this) {
            self::New => __('Move back to new'),
            self::Contacted => __('Mark as contacted'),
            self::VisitScheduled => __('Mark visit scheduled'),
            self::Won => __('Close as won'),
            self::Lost => __('Close as lost'),
        };
    }
}
