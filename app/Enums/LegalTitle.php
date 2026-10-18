<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LegalTitle: string implements HasLabel
{
    case Titled = 'titled';
    case Melkia = 'melkia';
    case Untitled = 'untitled';
    case Habous = 'habous';
    case Collective = 'collective';

    public function getLabel(): string
    {
        return match ($this) {
            self::Titled => __('Titled (Titre foncier)'),
            self::Melkia => __('Melkia'),
            self::Untitled => __('Untitled'),
            self::Habous => __('Habous'),
            self::Collective => __('Collective land'),
        };
    }
}
