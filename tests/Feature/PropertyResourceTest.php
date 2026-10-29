<?php

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Filament\Exports\PropertyExporter;
use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Properties\RelationManagers\InquiriesRelationManager;
use App\Models\Amenity;
use App\Models\City;
use App\Models\Inquiry;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\PropertyTypeSeeder;
use Filament\Actions\ExportAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PropertyTypeSeeder::class);
    $this->city = City::factory()->create();
    $this->actingAs(User::factory()->create());
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

test('the edit page loads the private information and keeps it on save', function () {
    $owner = Owner::factory()->create();
    $property = Property::factory()
        ->for(PropertyType::firstWhere('code', 'APT'))
        ->for($owner)
        ->create(['address' => 'Hay Al Wahda', 'internal_notes' => 'Keys at the café']);

    Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
        ->assertSchemaStateSet([
            'owner_id' => $owner->id,
            'address' => 'Hay Al Wahda',
            'internal_notes' => 'Keys at the café',
        ])
        ->fillForm(['price' => 999000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($property->fresh())
        ->owner_id->toBe($owner->id)
        ->address->toBe('Hay Al Wahda')
        ->internal_notes->toBe('Keys at the café');
});

test('the view page shows the listing with its details, private information and inquiries', function () {
    $owner = Owner::factory()->create(['name_en' => 'Hassan Idrissi']);
    $property = Property::factory()
        ->for(PropertyType::firstWhere('code', 'LND'))
        ->for($owner)
        ->create([
            'address' => 'Route de Zagora km 3',
            'details' => ['zoning' => 'agricultural', 'fenced' => true, 'utilities' => ['water', 'electricity'], 'buildable' => 'R+2'],
        ]);

    Livewire::test(ViewProperty::class, ['record' => $property->getRouteKey()])
        ->assertOk()
        ->assertSee($property->reference)
        ->assertSee('Hassan Idrissi')
        ->assertSee('Route de Zagora km 3')
        ->assertSee('Zoning: Agricultural')
        ->assertSee('Fenced: Yes')
        ->assertSee('Utilities: Water, Electricity')
        ->assertSee('Buildable: R+2')
        ->assertSeeLivewire(InquiriesRelationManager::class);
});

test('the view page strips scripts from the description', function () {
    $property = Property::factory()->create(['description_ar' => '<p>وصف</p><script>alert("xss")</script>']);

    Livewire::test(ViewProperty::class, ['record' => $property->getRouteKey()])
        ->assertOk()
        ->assertSee('وصف')
        ->assertDontSee('<script>alert("xss")</script>', escape: false);
});

test('duplicating a listing creates a draft copy with its amenities but without photos', function () {
    $amenity = Amenity::factory()->create();
    $property = Property::factory()
        ->published()
        ->featured()
        ->hasAttached($amenity, relationship: 'amenities')
        ->has(PropertyImage::factory(), 'images')
        ->create(['title_en' => 'Villa with pool']);

    $component = Livewire::test(ListProperties::class)
        ->callAction(TestAction::make('replicate')->table($property));

    $copy = Property::query()->whereKeyNot($property->id)->sole();

    $component->assertRedirect(PropertyResource::getUrl('edit', ['record' => $copy]));

    expect($copy)
        ->status->toBe(PropertyStatus::Draft)
        ->featured->toBeFalse()
        ->published_at->toBeNull()
        ->title_en->toBe('Villa with pool')
        ->reference->not->toBe($property->reference)
        ->slug->toBe('villa-with-pool-'.strtolower($copy->reference))
        ->and($copy->amenities->modelKeys())->toBe([$amenity->id])
        ->and($copy->images)->toBeEmpty();
});

test('the status tabs show their counts and filter the list', function () {
    $drafts = Property::factory()->count(2)->create();
    $published = Property::factory()->published()->create();

    $component = Livewire::test(ListProperties::class);
    $tabs = $component->instance()->getTabs();

    expect($tabs['all']->getBadge())->toBe('3')
        ->and($tabs['draft']->getBadge())->toBe('2')
        ->and($tabs['published']->getBadge())->toBe('1')
        ->and($tabs['archived']->getBadge())->toBeNull();

    $component->set('activeTab', 'published')
        ->assertCanSeeTableRecords([$published])
        ->assertCanNotSeeTableRecords($drafts);
});

test('the table shows days on market, inquiries and photos per listing', function () {
    $this->travelTo(Carbon::create(2026, 10, 21, 12));
    $property = Property::factory()->published()->create(['published_at' => Carbon::create(2026, 10, 9, 8)]);
    Inquiry::factory()->count(2)->for($property)->create();
    PropertyImage::factory()->for($property)->create();
    $draft = Property::factory()->create();

    Livewire::test(ListProperties::class)
        ->assertTableColumnStateSet('days_on_market', 12, $property)
        ->assertTableColumnStateSet('inquiries_count', 2, $property)
        ->assertTableColumnStateSet('images_count', 1, $property)
        ->assertTableColumnStateSet('days_on_market', null, $draft)
        ->assertTableColumnStateSet('images_count', 0, $draft);
});

test('sorting by days on market follows the publication date', function () {
    $older = Property::factory()->published()->create(['published_at' => now()->subDays(40)]);
    $recent = Property::factory()->published()->create(['published_at' => now()->subDays(3)]);

    Livewire::test(ListProperties::class)
        ->sortTable('days_on_market')
        ->assertCanSeeTableRecords([$recent, $older], inOrder: true)
        ->sortTable('days_on_market', 'desc')
        ->assertCanSeeTableRecords([$older, $recent], inOrder: true);
});

test('photos dropped in bulk are added to a new listing in order with the first as cover', function () {
    Storage::fake(PropertyImage::DISK);

    Livewire::test(CreateProperty::class)
        ->fillForm(listingFormData('APT', [
            'status' => PropertyStatus::Published->value,
            'new_photos' => [
                UploadedFile::fake()->image('salon.jpg', 300, 200),
                UploadedFile::fake()->image('kitchen.jpg', 400, 200),
            ],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $images = Property::sole()->images;
    $widths = $images->map(fn (PropertyImage $image): int => getimagesize(Storage::disk(PropertyImage::DISK)->path($image->path))[0]);

    expect($images->pluck('position')->all())->toBe([1, 2])
        ->and($images->pluck('is_cover')->all())->toBe([true, false])
        ->and($widths->all())->toBe([300, 400]);
});

test('photos dropped in bulk on the edit page are appended and survive the next save', function () {
    Storage::fake(PropertyImage::DISK);
    $property = Property::factory()->for(PropertyType::firstWhere('code', 'APT'))->create();
    $existing = PropertyImage::factory()->for($property)->cover()->create([
        'path' => UploadedFile::fake()->image('facade.jpg')->store(PropertyImage::DIRECTORY, PropertyImage::DISK),
        'position' => 1,
    ]);

    Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
        ->fillForm(['new_photos' => [UploadedFile::fake()->image('terrace.jpg')]])
        ->call('save')
        ->assertHasNoFormErrors()
        ->call('save')
        ->assertHasNoFormErrors();

    $images = $property->images()->get();

    expect($images)->toHaveCount(2)
        ->and($images->first()->is($existing))->toBeTrue()
        ->and($images->last())
        ->position->toBe(2)
        ->is_cover->toBeFalse();
});

test('the pin and the outline drawn on the map are saved', function () {
    $property = Property::factory()->for(PropertyType::firstWhere('code', 'LND'))->create();

    Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
        ->fillForm([
            'latitude' => 30.935,
            'longitude' => -6.935,
            'boundary' => [...squarePlot(), 'properties' => ['injected' => true]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($property->fresh())
        ->latitude->toBe('30.9350000')
        ->longitude->toBe('-6.9350000')
        ->boundary->toBe(squarePlot());
});

test('an invalid outline is not saved', function () {
    $property = Property::factory()->for(PropertyType::firstWhere('code', 'LND'))->create();

    Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
        ->fillForm(['boundary' => ['type' => 'Point', 'coordinates' => [-6.94, 30.93]]])
        ->call('save')
        ->assertHasFormErrors(['boundary']);

    expect($property->fresh()->boundary)->toBeNull();
});

test('the view page shows the map only when the listing has a location', function () {
    $withOutline = Property::factory()->create(['boundary' => squarePlot()]);
    $withoutLocation = Property::factory()->create();

    Livewire::test(ViewProperty::class, ['record' => $withOutline->getRouteKey()])
        ->assertOk()
        ->assertSee('Location on map');

    Livewire::test(ViewProperty::class, ['record' => $withoutLocation->getRouteKey()])
        ->assertOk()
        ->assertDontSee('Location on map');
});

test('amenities are offered for built and commercial listings but not for land', function () {
    Livewire::test(CreateProperty::class)
        ->fillForm(['property_type_id' => PropertyType::firstWhere('code', 'VIL')->id])
        ->assertFormFieldVisible('amenities')
        ->fillForm(['property_type_id' => PropertyType::firstWhere('code', 'HOT')->id])
        ->assertFormFieldVisible('amenities')
        ->fillForm(['property_type_id' => PropertyType::firstWhere('code', 'LND')->id])
        ->assertFormFieldHidden('amenities');
});

test('listings are exported from the table', function () {
    $exports = ExportAction::fake();

    Livewire::test(ListProperties::class)
        ->callAction(TestAction::make('export')->table())
        ->assertHasNoFormErrors();

    $exports->assertDispatched(PropertyExporter::class);
});
