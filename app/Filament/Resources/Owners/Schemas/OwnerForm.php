<?php

namespace App\Filament\Resources\Owners\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Also used to create an owner from the listing form.
 */
class OwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name_en')
                    ->label(__('Name (English)'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->maxLength(255)
                    ->extraInputAttributes(['dir' => 'rtl']),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(30),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label(__('Internal notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
