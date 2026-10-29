<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Channel the lead came through.
 */
enum InquirySource: string implements HasLabel
{
    case Form = 'form';
    case VisitForm = 'visit_form';
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case WalkIn = 'walk_in';
    case Social = 'social';

    public function getLabel(): string
    {
        return match ($this) {
            self::Form => __('Website form'),
            self::VisitForm => __('Website visit request'),
            self::WhatsApp => __('WhatsApp'),
            self::Phone => __('Phone call'),
            self::WalkIn => __('Walk-in'),
            self::Social => __('Social media'),
        };
    }
}
