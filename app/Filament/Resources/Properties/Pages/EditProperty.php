<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Properties\Schemas\PropertyForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    /**
     * Files from the bulk photo field, turned into images after saving.
     *
     * @var array<string>
     */
    protected array $newPhotoPaths = [];

    /**
     * The private columns are #[Hidden] on the model, and Filament fills the form from attributesToArray(),
     * which leaves hidden attributes out. Without this they would load empty and be wiped on save.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        return [...$data, ...$record->only($record->getHidden())];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->newPhotoPaths = Arr::pull($data, PropertyForm::NEW_PHOTOS_FIELD) ?? [];

        return $data;
    }

    /**
     * The photo gallery is reloaded after adding the bulk photos: the repeater deletes images missing from its state,
     * so a second save would otherwise remove them.
     */
    protected function afterSave(): void
    {
        $this->getRecord()->addImages($this->newPhotoPaths);
        $this->getRecord()->ensureCoverImage();

        $this->refreshFormData(['images', PropertyForm::NEW_PHOTOS_FIELD]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
