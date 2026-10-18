<?php

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting a new cover unsets the previous cover of the same property', function () {
    $property = Property::factory()->create();
    $otherProperty = Property::factory()->create();

    $oldCover = PropertyImage::factory()->for($property)->cover()->create();
    $otherCover = PropertyImage::factory()->for($otherProperty)->cover()->create();
    $newCover = PropertyImage::factory()->for($property)->create();

    $newCover->update(['is_cover' => true]);

    expect($oldCover->fresh()->is_cover)->toBeFalse()
        ->and($newCover->fresh()->is_cover)->toBeTrue()
        ->and($otherCover->fresh()->is_cover)->toBeTrue()
        ->and($property->coverImage->is($newCover))->toBeTrue();
});
