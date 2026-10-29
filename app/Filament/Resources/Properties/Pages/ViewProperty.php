<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\Actions\PropertyActions;
use App\Filament\Resources\Properties\PropertyResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PropertyActions::openOnWebsite(),
            ActionGroup::make(PropertyActions::changeStatus())
                ->label(__('Change status'))
                ->button()
                ->color('gray'),
            EditAction::make(),
        ];
    }
}
