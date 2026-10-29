<?php

namespace App\Filament\Resources\Owners\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OwnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('name_en')
                    ->label(__('Name (English)')),
                TextEntry::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->extraAttributes(['dir' => 'rtl'])
                    ->placeholder('—'),
                TextEntry::make('phone')
                    ->url(fn (?string $state): ?string => $state === null ? null : "tel:{$state}")
                    ->copyable()
                    ->placeholder('—'),
                TextEntry::make('email')
                    ->url(fn (?string $state): ?string => $state === null ? null : "mailto:{$state}")
                    ->placeholder('—'),
                TextEntry::make('notes')
                    ->label(__('Internal notes'))
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }
}
