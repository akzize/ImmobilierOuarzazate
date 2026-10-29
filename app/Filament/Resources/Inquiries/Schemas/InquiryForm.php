<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\Language;
use App\Models\Property;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Livewire\Component;

/**
 * Shared by the Inquiries resource and the listing's Inquiries tab, where the listing is implied.
 */
class InquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('property_id')
                    ->label(__('Listing'))
                    ->relationship('property', 'reference')
                    ->getOptionLabelFromRecordUsing(fn (Property $record): string => $record->reference.' · '.($record->title_en ?: $record->title_ar))
                    ->searchable(['reference', 'title_ar', 'title_en'])
                    ->placeholder(__('General inquiry'))
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof RelationManager)
                    ->columnSpanFull(),
                TextInput::make('full_name')
                    ->label(__('Full name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(30),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Select::make('lang')
                    ->label(__('Language'))
                    ->options(Language::class)
                    ->default(Language::Arabic)
                    ->required(),
                Select::make('source')
                    ->options(InquirySource::class)
                    ->default(InquirySource::Phone)
                    ->required(),
                DateTimePicker::make('preferred_visit_at')
                    ->label(__('Preferred visit time'))
                    ->seconds(false),
                Textarea::make('message')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(InquiryStatus::class)
                    ->default(InquiryStatus::New)
                    ->required(),
                Textarea::make('admin_notes')
                    ->label(__('Internal notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
