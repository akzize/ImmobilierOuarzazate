<?php

use App\Enums\PropertyStatus;
use App\Models\City;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('days on market count from the first publication while the listing is on the market', function (PropertyStatus $status, ?int $expectedDays) {
    $this->travelTo(Carbon::create(2026, 10, 21, 12));
    $property = Property::factory()->create(['status' => $status, 'published_at' => Carbon::create(2026, 9, 1, 9)]);

    expect($property->daysOnMarket())->toBe($expectedDays);
})->with([
    'published' => [PropertyStatus::Published, 50],
    'reserved' => [PropertyStatus::Reserved, 50],
    'back to draft' => [PropertyStatus::Draft, null],
    'sold or rented' => [PropertyStatus::SoldOrRented, null],
    'archived' => [PropertyStatus::Archived, null],
]);

test('references are numbered sequentially per year', function () {
    $this->travelTo(Carbon::create(2026, 5, 1));

    $first = Property::factory()->create();
    $second = Property::factory()->create();

    $this->travelTo(Carbon::create(2027, 1, 1));

    $nextYear = Property::factory()->create();

    expect($first->reference)->toBe('OZZ-2026-0001')
        ->and($second->reference)->toBe('OZZ-2026-0002')
        ->and($nextYear->reference)->toBe('OZZ-2027-0001');
});

test('slug falls back to type, city and reference without an english title', function () {
    $this->travelTo(Carbon::create(2026, 5, 1));

    $property = Property::factory()
        ->for(PropertyType::factory()->state(['label_en' => 'Villa']))
        ->for(City::factory()->state(['name_en' => 'Ouarzazate']))
        ->create(['title_en' => null]);

    expect($property->slug)->toBe('villa-ouarzazate-ozz-2026-0001');
});

test('slug is built from the english title and gets the reference when taken', function () {
    $this->travelTo(Carbon::create(2026, 5, 1));

    $first = Property::factory()->create(['title_en' => 'Villa with pool']);
    $second = Property::factory()->create(['title_en' => 'Villa with pool']);

    expect($first->slug)->toBe('villa-with-pool')
        ->and($second->slug)->toBe('villa-with-pool-ozz-2026-0002');
});

test('slug is replaced only the first time the english title is filled', function () {
    $property = Property::factory()->create(['title_en' => null]);

    $property->update(['title_en' => 'Riad in the old town']);

    expect($property->slug)->toBe('riad-in-the-old-town');

    $property->update(['title_en' => 'Renovated riad']);

    expect($property->slug)->toBe('riad-in-the-old-town');
});

test('published_at is stamped on first publication only', function () {
    $this->travelTo(Carbon::create(2026, 5, 1, 10));

    $property = Property::factory()->create();

    expect($property->published_at)->toBeNull();

    $property->update(['status' => PropertyStatus::Published]);
    $firstPublishedAt = $property->published_at;

    $this->travelTo(Carbon::create(2026, 6, 1, 10));
    $property->update(['status' => PropertyStatus::Reserved]);
    $property->update(['status' => PropertyStatus::Published]);

    expect($firstPublishedAt->toDateTimeString())->toBe('2026-05-01 10:00:00')
        ->and($property->published_at->toDateTimeString())->toBe('2026-05-01 10:00:00');
});

test('private fields are never serialized', function () {
    $property = Property::factory()
        ->for(Owner::factory())
        ->create(['address' => 'Hay Al Wahda, Ouarzazate', 'internal_notes' => 'Owner abroad']);

    expect($property->toArray())->not->toHaveKeys(['owner_id', 'address', 'internal_notes']);
});

test('published scope only returns published listings', function () {
    Property::factory()->create();
    $published = Property::factory()->published()->create();

    expect(Property::published()->pluck('id')->all())->toBe([$published->id]);
});
