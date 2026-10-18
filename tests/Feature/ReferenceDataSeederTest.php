<?php

use App\Enums\PropertyGroup;
use App\Models\Amenity;
use App\Models\City;
use App\Models\PropertyType;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\PropertyTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('reference data seeders are idempotent', function () {
    $seeders = [PropertyTypeSeeder::class, CitySeeder::class, AmenitySeeder::class];

    $this->seed($seeders);
    $this->seed($seeders);

    expect(PropertyType::count())->toBe(12)
        ->and(City::count())->toBe(10)
        ->and(Amenity::count())->toBe(13)
        ->and(PropertyType::firstWhere('code', 'FARM')->group)->toBe(PropertyGroup::Land)
        ->and(PropertyType::firstWhere('code', 'HOT')->group)->toBe(PropertyGroup::Commercial);
});
