<?php

use App\Filament\Resources\Amenities\Pages\ManageAmenities;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('an amenity is added with a lowercase code', function () {
    Livewire::test(ManageAmenities::class)
        ->callAction('create', data: ['code' => 'garage', 'label_en' => 'Garage', 'label_ar' => 'مرآب'])
        ->assertHasNoFormErrors()
        ->callAction('create', data: ['code' => 'Solar heater', 'label_en' => 'Solar heater', 'label_ar' => 'سخان شمسي'])
        ->assertHasFormErrors(['code' => 'regex']);

    expect(Amenity::sole())
        ->code->toBe('garage')
        ->label_en->toBe('Garage');
});

test('the code of an amenity never changes', function () {
    $amenity = Amenity::factory()->create(['code' => 'pool']);

    Livewire::test(ManageAmenities::class)
        ->callAction(TestAction::make('edit')->table($amenity), data: ['code' => 'swimming_pool', 'label_en' => 'Swimming pool'])
        ->assertHasNoFormErrors();

    expect($amenity->fresh())
        ->code->toBe('pool')
        ->label_en->toBe('Swimming pool');
});

test('an amenity used by listings cannot be deleted', function () {
    $usedAmenity = Amenity::factory()->hasAttached(Property::factory())->create();
    $unusedAmenity = Amenity::factory()->create();

    Livewire::test(ManageAmenities::class)
        ->assertActionHidden(TestAction::make('delete')->table($usedAmenity))
        ->assertActionVisible(TestAction::make('delete')->table($unusedAmenity));
});
