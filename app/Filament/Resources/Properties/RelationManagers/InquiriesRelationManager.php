<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\Inquiries\Schemas\InquiryForm;
use App\Filament\Resources\Inquiries\Tables\InquiriesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
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
        return InquiryForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return InquiriesTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->label(__('Log inquiry')),
            ]);
    }
}
