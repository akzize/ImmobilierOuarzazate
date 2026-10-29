<?php

use App\Filament\Resources\Owners\Pages\ListOwners;
use App\Filament\Resources\Owners\Pages\ViewOwner;
use App\Filament\Resources\Owners\RelationManagers\PropertiesRelationManager;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('an owner is added from the list', function () {
    Livewire::test(ListOwners::class)
        ->callAction('create', data: [
            'name_en' => 'Hassan Idrissi',
            'name_ar' => 'حسن الإدريسي',
            'phone' => '0661234567',
            'email' => 'hassan@example.com',
            'notes' => 'Lives in Agadir, keys at the agency.',
        ])
        ->assertHasNoFormErrors();

    expect(Owner::sole())
        ->name_en->toBe('Hassan Idrissi')
        ->email->toBe('hassan@example.com')
        ->notes->toBe('Lives in Agadir, keys at the agency.');
});

test('an owner needs an English name', function () {
    Livewire::test(ListOwners::class)
        ->callAction('create', data: ['name_en' => null])
        ->assertHasFormErrors(['name_en' => 'required']);

    expect(Owner::exists())->toBeFalse();
});

test('the table shows how many listings each owner has', function () {
    $owner = Owner::factory()->has(Property::factory()->count(2))->create();

    Livewire::test(ListOwners::class)
        ->assertTableColumnStateSet('properties_count', 2, $owner);
});

test('the view page lists only the owner\'s listings', function () {
    $owner = Owner::factory()->create();
    $listings = Property::factory()->count(2)->for($owner)->create();
    $otherListing = Property::factory()->for(Owner::factory())->create();

    Livewire::test(ViewOwner::class, ['record' => $owner->getRouteKey()])
        ->assertOk();

    Livewire::test(PropertiesRelationManager::class, ['ownerRecord' => $owner, 'pageClass' => ViewOwner::class])
        ->assertCanSeeTableRecords($listings)
        ->assertCanNotSeeTableRecords([$otherListing]);
});

test('an owner is edited from the view page', function () {
    $owner = Owner::factory()->create();

    Livewire::test(ViewOwner::class, ['record' => $owner->getRouteKey()])
        ->callAction('edit', data: ['phone' => '0700000000'])
        ->assertHasNoFormErrors();

    expect($owner->fresh()->phone)->toBe('0700000000');
});

test('deleting an owner keeps their listings', function () {
    $owner = Owner::factory()->create();
    $listing = Property::factory()->for($owner)->create();

    Livewire::test(ListOwners::class)
        ->callAction(TestAction::make('delete')->table($owner));

    expect(Owner::exists())->toBeFalse()
        ->and($listing->fresh()->owner_id)->toBeNull();
});

test('a new owner can be created from the listing form', function () {
    $property = Property::factory()->create();

    Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
        ->callAction(TestAction::make('createOption')->schemaComponent('owner_id'), data: [
            'name_en' => 'Hassan Idrissi',
            'email' => 'hassan@example.com',
        ])
        ->assertHasNoErrors();

    expect(Owner::sole())
        ->name_en->toBe('Hassan Idrissi')
        ->email->toBe('hassan@example.com');
});
