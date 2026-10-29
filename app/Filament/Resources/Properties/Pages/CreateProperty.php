<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Properties\Schemas\PropertyForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Arr;

class CreateProperty extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = PropertyResource::class;

    /**
     * Files from the bulk photo field, turned into images once the listing exists.
     *
     * @var array<string>
     */
    protected array $newPhotoPaths = [];

    /**
     * @return array<Step>
     */
    protected function getSteps(): array
    {
        return PropertyForm::steps();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->newPhotoPaths = Arr::pull($data, PropertyForm::NEW_PHOTOS_FIELD) ?? [];

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->getRecord()->addImages($this->newPhotoPaths);
        $this->getRecord()->ensureCoverImage();
    }
}
