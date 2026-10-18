<?php

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Models\Amenity;
use App\Models\City;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\PropertyTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PropertyTypeSeeder::class);
    $this->city = City::factory()->create();
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * @return array<string, mixed>
 */
function listingFormData(string $typeCode, array $overrides = []): array
{
    return [
        'property_type_id' => PropertyType::firstWhere('code', $typeCode)->id,
        'transaction' => TransactionType::Sale->value,
        'status' => PropertyStatus::Draft->value,
        'city_id' => test()->city->id,
        'title_ar' => 'فيلا للبيع',
        'description_ar' => '<p>وصف</p>',
        'price' => 1500000,
        'surface_m2' => 300,
        ...$overrides,
    ];
}

test('the list page shows listings', function () {
    $properties = Property::factory()->count(2)->create();

    Livewire::test(ListProperties::class)
        ->assertOk()
        ->assertCanSeeTableRecords($properties);
});

test('a built property is created with its type specific details and amenities', function () {
    $pool = Amenity::factory()->create(['label_en' => 'Pool']);

    Livewire::test(CreateProperty::class)
        ->fillForm(listingFormData('VIL', [
            'bedrooms' => 4,
            'details' => ['living_rooms' => 2, 'condition' => 'new'],
            'amenities' => [$pool->id],
            'address' => 'Hay Al Wahda',
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $property = Property::sole();

    expect($property->reference)->toStartWith('OZZ-')
        ->and($property->bedrooms)->toBe(4)
        ->and($property->details)->toMatchArray(['living_rooms' => 2, 'condition' => 'new'])
        ->and($property->amenities->pluck('id')->all())->toBe([$pool->id])
        ->and($property->address)->toBe('Hay Al Wahda');
});

test('only the sections of the selected type group are shown', function () {
    Livewire::test(CreateProperty::class)
        ->fillForm(['property_type_id' => PropertyType::firstWhere('code', 'LND')->id])
        ->assertFormFieldVisible('details.zoning')
        ->assertFormFieldHidden('bedrooms')
        ->assertFormFieldHidden('details.activity_allowed')
        ->assertFormFieldHidden('details.water_source')
        ->fillForm(['property_type_id' => PropertyType::firstWhere('code', 'FARM')->id])
        ->assertFormFieldVisible('details.water_source');
});

test('money fields follow the transaction and type group', function () {
    Livewire::test(CreateProperty::class)
        ->fillForm([
            'property_type_id' => PropertyType::firstWhere('code', 'APT')->id,
            'transaction' => TransactionType::Sale->value,
        ])
        ->assertFormFieldVisible('details.monthly_charges')
        ->assertFormFieldHidden('details.deposit')
        ->assertFormFieldHidden('details.goodwill')
        ->fillForm([
            'property_type_id' => PropertyType::firstWhere('code', 'SHOP')->id,
            'transaction' => TransactionType::Rent->value,
        ])
        ->assertFormFieldVisible('details.deposit')
        ->assertFormFieldVisible('details.goodwill')
        ->assertFormFieldHidden('details.monthly_charges');
});

test('reference and slug are only shown once the listing exists', function () {
    Livewire::test(CreateProperty::class)
        ->assertDontSee(__('First published'));
});

test('a land listing stores land details', function () {
    Livewire::test(CreateProperty::class)
        ->fillForm(listingFormData('LND', [
            'details' => ['zoning' => 'residential', 'fenced' => true, 'utilities' => ['water']],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Property::sole()->details)->toMatchArray([
        'zoning' => 'residential',
        'fenced' => true,
        'utilities' => ['water'],
    ]);
});

test('the create wizard validates the listing details step before moving on', function () {
    Livewire::test(CreateProperty::class)
        ->fillForm(['title_ar' => null])
        ->goToNextWizardStep()
        ->assertHasFormErrors(['property_type_id' => 'required', 'title_ar' => 'required', 'price' => 'required']);
});

test('required fields are validated', function () {
    Livewire::test(CreateProperty::class)
        ->fillForm(listingFormData('APT', ['title_ar' => null, 'price' => null]))
        ->call('create')
        ->assertHasFormErrors(['title_ar' => 'required', 'price' => 'required']);
});

test('a listing cannot be published without images', function () {
    Livewire::test(CreateProperty::class)
        ->fillForm(listingFormData('APT', ['status' => PropertyStatus::Published->value]))
        ->call('create')
        ->assertHasFormErrors(['images']);

    expect(Property::count())->toBe(0);
});

test('the edit page loads and saves changes', function () {
    $property = Property::factory()
        ->for(PropertyType::firstWhere('code', 'APT'))
        ->create();

    Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
        ->assertOk()
        ->assertSee($property->reference)
        ->fillForm(['price' => 999000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $property->fresh()->price)->toBe(999000.0);
});
