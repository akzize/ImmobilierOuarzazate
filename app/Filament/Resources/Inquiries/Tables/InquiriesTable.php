<?php

namespace App\Filament\Resources\Inquiries\Tables;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\Language;
use App\Filament\Resources\Inquiries\Actions\InquiryActions;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Inquiry;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Component;

/**
 * Shared by the Inquiries resource and the listing's Inquiries tab, where the listing column is hidden.
 */
class InquiriesTable
{
    public static function configure(Table $table): Table
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
                TextColumn::make('property.reference')
                    ->label(__('Listing'))
                    ->placeholder(__('General'))
                    ->url(fn (Inquiry $record): ?string => $record->property ? PropertyResource::getUrl('view', ['record' => $record->property]) : null)
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof RelationManager),
                TextColumn::make('message')
                    ->limit(60)
                    ->tooltip(fn (Inquiry $record): string => $record->message)
                    ->toggleable(),
                TextColumn::make('lang')
                    ->label(__('Language'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('source')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('preferred_visit_at')
                    ->label(__('Preferred visit'))
                    ->dateTime()
                    ->placeholder('—')
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
                SelectFilter::make('source')
                    ->options(InquirySource::class),
                SelectFilter::make('lang')
                    ->label(__('Language'))
                    ->options(Language::class),
            ])
            ->recordActions([
                InquiryActions::whatsApp(),
                EditAction::make(),
                ActionGroup::make([
                    ActionGroup::make(InquiryActions::changeStatus())->dropdown(false),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
