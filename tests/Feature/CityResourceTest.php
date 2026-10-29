<?php

use App\Filament\Resources\Cities\Pages\ManageCities;
use App\Models\City;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a city is added with both names', function () {
    Livewire::test(ManageCities::class)
        ->callAction('create', data: ['name_en' => 'Skoura', 'name_ar' => 'سكورة'])
        ->assertHasNoFormErrors();

    expect(City::sole())
        ->name_en->toBe('Skoura')
        ->name_ar->toBe('سكورة');
});

test('the Arabic name must be written in Arabic', function () {
    Livewire::test(ManageCities::class)
        ->callAction('create', data: ['name_en' => 'Skoura', 'name_ar' => 'Skoura'])
        ->assertHasFormErrors(['name_ar']);

    expect(City::exists())->toBeFalse();
});

test('a city name cannot be added twice', function () {
    City::factory()->create(['name_en' => 'Skoura']);

    Livewire::test(ManageCities::class)
        ->callAction('create', data: ['name_en' => 'Skoura', 'name_ar' => 'سكورة'])
        ->assertHasFormErrors(['name_en' => 'unique']);
});

test('a city used by listings cannot be deleted', function () {
    $usedCity = City::factory()->has(Property::factory())->create();
    $unusedCity = City::factory()->create();

    Livewire::test(ManageCities::class)
        ->assertActionHidden(TestAction::make('delete')->table($usedCity))
        ->assertActionVisible(TestAction::make('delete')->table($unusedCity));
});
