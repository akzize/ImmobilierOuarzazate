<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Properties\Schemas\PropertyForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;

class CreateProperty extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = PropertyResource::class;

    /**
     * @return array<Step>
     */
    protected function getSteps(): array
    {
        return PropertyForm::steps();
    }

    protected function afterCreate(): void
    {
        $this->getRecord()->ensureCoverImage();
    }
}
