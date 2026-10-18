<?php

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('image files are deleted with their property and when replaced', function () {
    Storage::fake(PropertyImage::DISK);

    $oldPath = UploadedFile::fake()->image('old.jpg')->store(PropertyImage::DIRECTORY, PropertyImage::DISK);
    $newPath = UploadedFile::fake()->image('new.jpg')->store(PropertyImage::DIRECTORY, PropertyImage::DISK);

    $image = PropertyImage::factory()->create(['path' => $oldPath]);
    $image->update(['path' => $newPath]);

    Storage::disk(PropertyImage::DISK)->assertMissing($oldPath);
    Storage::disk(PropertyImage::DISK)->assertExists($newPath);

    $image->property->delete();

    Storage::disk(PropertyImage::DISK)->assertMissing($newPath);
    expect(PropertyImage::count())->toBe(0);
});

test('the first photo becomes the cover when none is marked', function () {
    $property = Property::factory()->create();
    $second = PropertyImage::factory()->for($property)->create(['position' => 2]);
    $first = PropertyImage::factory()->for($property)->create(['position' => 1]);

    $property->ensureCoverImage();

    expect($first->fresh()->is_cover)->toBeTrue()
        ->and($second->fresh()->is_cover)->toBeFalse();
});

test('an explicitly chosen cover is kept', function () {
    $property = Property::factory()->create();
    PropertyImage::factory()->for($property)->create(['position' => 1]);
    $chosen = PropertyImage::factory()->for($property)->cover()->create(['position' => 2]);

    $property->ensureCoverImage();

    expect($property->coverImage->is($chosen))->toBeTrue();
});

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
