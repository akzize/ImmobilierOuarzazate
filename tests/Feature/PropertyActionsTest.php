<?php

use App\Enums\PropertyStatus;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('a listing with a photo is published from the table', function () {
    $property = Property::factory()->has(PropertyImage::factory(), 'images')->create();

    Livewire::test(ListProperties::class)
        ->callAction(TestAction::make('markAsPublished')->table($property))
        ->assertNotified('Listing marked as Published');

    expect($property->fresh())
        ->status->toBe(PropertyStatus::Published)
        ->published_at->not->toBeNull();
});

test('a listing without photos is not published', function () {
    $property = Property::factory()->create();

    Livewire::test(ListProperties::class)
        ->callAction(TestAction::make('markAsPublished')->table($property))
        ->assertNotified('Add at least one photo before publishing.');

    expect($property->fresh())
        ->status->toBe(PropertyStatus::Draft)
        ->published_at->toBeNull();
});

test('the quick action for the current status is hidden', function () {
    $property = Property::factory()->create();

    Livewire::test(ListProperties::class)
        ->assertActionHidden(TestAction::make('markAsDraft')->table($property))
        ->assertActionVisible(TestAction::make('markAsArchived')->table($property));
});

test('a listing is archived from its view page', function () {
    $property = Property::factory()->published()->create();

    Livewire::test(ViewProperty::class, ['record' => $property->getRouteKey()])
        ->callAction('markAsArchived')
        ->assertNotified('Listing marked as Archived');

    expect($property->fresh()->status)->toBe(PropertyStatus::Archived);
});

test('a bulk status change updates every selected listing', function () {
    $properties = Property::factory()->count(2)->published()->create();

    Livewire::test(ListProperties::class)
        ->selectTableRecords($properties->modelKeys())
        ->callAction(TestAction::make('changeStatus')->table()->bulk(), data: ['status' => PropertyStatus::Archived->value])
        ->assertNotified('Status updated');

    expect($properties->map->fresh()->pluck('status')->all())->toBe([PropertyStatus::Archived, PropertyStatus::Archived]);
});

test('a bulk publish skips listings without photos', function () {
    $withPhoto = Property::factory()->has(PropertyImage::factory(), 'images')->create();
    $withoutPhoto = Property::factory()->create();

    Livewire::test(ListProperties::class)
        ->selectTableRecords([$withPhoto->id, $withoutPhoto->id])
        ->callAction(TestAction::make('changeStatus')->table()->bulk(), data: ['status' => PropertyStatus::Published->value])
        ->assertNotified('1 of 2 listings updated');

    expect($withPhoto->fresh()->status)->toBe(PropertyStatus::Published)
        ->and($withoutPhoto->fresh()->status)->toBe(PropertyStatus::Draft);
});

test('published listings link to their page on the website', function () {
    config(['app.frontend_url' => 'https://3a9arouarzazate.com']);
    $property = Property::factory()->published()->create(['title_en' => 'Riad with patio']);

    Livewire::test(ListProperties::class)
        ->assertActionHasUrl(TestAction::make('openOnWebsite')->table($property), 'https://3a9arouarzazate.com/marketplace/riad-with-patio')
        ->assertActionShouldOpenUrlInNewTab(TestAction::make('openOnWebsite')->table($property));
});

test('unpublished listings have no website link', function () {
    config(['app.frontend_url' => 'https://3a9arouarzazate.com']);
    $property = Property::factory()->create();

    Livewire::test(ListProperties::class)
        ->assertActionHidden(TestAction::make('openOnWebsite')->table($property));
});
