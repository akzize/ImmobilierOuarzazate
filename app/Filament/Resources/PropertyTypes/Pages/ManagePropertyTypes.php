<?php

namespace App\Filament\Resources\PropertyTypes\Pages;

use App\Filament\Resources\PropertyTypes\PropertyTypeResource;
use App\Models\PropertyType;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePropertyTypes extends ManageRecords
{
    protected static string $resource = PropertyTypeResource::class;

    /**
     * New types go to the end of the list.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => [...$data, 'position' => PropertyType::max('position') + 1]),
        ];
    }
}
