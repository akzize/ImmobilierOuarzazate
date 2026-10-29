<?php

namespace App\Filament\Widgets;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\Actions\InquiryActions;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Inquiry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * New inquiries, oldest first, with the two actions needed to answer them.
 */
class InquiriesToAnswer extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Inquiries to answer'))
            ->query(fn (): Builder => Inquiry::query()->where('status', InquiryStatus::New)->with('property'))
            ->defaultSort('created_at')
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('Name')),
                TextColumn::make('phone')
                    ->copyable(),
                TextColumn::make('property.reference')
                    ->label(__('Listing'))
                    ->placeholder(__('General'))
                    ->url(fn (Inquiry $record): ?string => $record->property ? PropertyResource::getUrl('view', ['record' => $record->property]) : null),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('Received'))
                    ->since()
                    ->dateTimeTooltip()
                    ->color(fn (Inquiry $record): ?string => $record->created_at->lt(now()->subDay()) ? 'danger' : null),
            ])
            ->recordActions([
                InquiryActions::whatsApp(),
                InquiryActions::changeStatusTo(InquiryStatus::Contacted),
            ])
            ->emptyStateHeading(__('No inquiries waiting'))
            ->emptyStateDescription(__('New inquiries from the website show up here.'))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
