<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Enums\InquiryStatus;
use App\Enums\Language;
use App\Models\Inquiry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Inquiries about one listing. Website inquiries arrive through the API; phone and walk-in leads can be logged here.
 */
class InquiriesRelationManager extends RelationManager
{
    protected static string $relationship = 'inquiries';

    /**
     * Follow-up (status, notes) is allowed from the View page too.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('Name'))
                    ->description(fn (Inquiry $record): ?string => $record->email)
                    ->searchable(['full_name', 'email']),
                TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('message')
                    ->limit(60)
                    ->tooltip(fn (Inquiry $record): string => $record->message)
                    ->toggleable(),
                TextColumn::make('lang')
                    ->label(__('Language'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('Received'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InquiryStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Log inquiry')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
