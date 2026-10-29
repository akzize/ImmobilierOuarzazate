<?php

namespace App\Observers;

use App\Enums\PropertyStatus;
use App\Models\Property;
use Illuminate\Support\Str;

class PropertyObserver
{
    /**
     * Assign the reference (e.g. OZZ-2026-0001) and the initial slug.
     */
    public function creating(Property $property): void
    {
        $property->reference ??= $this->nextReference();
        $property->slug ??= $this->makeSlug($property);
    }

    /**
     * Replace the fallback slug with one built from the English title the first time it is filled.
     */
    public function updating(Property $property): void
    {
        if ($property->isDirty('title_en') && blank($property->getOriginal('title_en')) && filled($property->title_en)) {
            $property->slug = $this->makeSlug($property);
        }
    }

    /**
     * Stamp the first publication date, and the deal figures when the listing is sold or rented
     * (the asking price and today unless given). Putting the listing back on the market clears them.
     */
    public function saving(Property $property): void
    {
        if ($property->status === PropertyStatus::Published && $property->published_at === null) {
            $property->published_at = now();
        }

        if ($property->status === PropertyStatus::SoldOrRented) {
            $property->closed_price ??= $property->price;
            $property->closed_at ??= now();
        } else {
            $property->closed_price = null;
            $property->closed_at = null;
        }
    }

    /**
     * Delete images through the model so their files are removed (the FK cascade skips model events).
     */
    public function deleting(Property $property): void
    {
        $property->images->each->delete();
    }

    private function nextReference(): string
    {
        $prefix = Property::REFERENCE_PREFIX.'-'.now()->year.'-';

        $lastReference = Property::query()
            ->where('reference', 'like', $prefix.'%')
            ->latest('id')
            ->value('reference');

        $nextNumber = $lastReference === null ? 1 : ((int) Str::after($lastReference, $prefix)) + 1;

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Built from the English title when present, otherwise from type + city + reference.
     * The reference is appended when the slug is already taken.
     */
    private function makeSlug(Property $property): string
    {
        $slug = Str::slug((string) $property->title_en);

        if ($slug === '') {
            return Str::slug(implode(' ', [
                $property->propertyType?->label_en,
                $property->city?->name_en,
                $property->reference,
            ]));
        }

        $isTaken = Property::query()
            ->where('slug', $slug)
            ->whereKeyNot($property->getKey())
            ->exists();

        return $isTaken ? $slug.'-'.Str::slug($property->reference) : $slug;
    }
}
