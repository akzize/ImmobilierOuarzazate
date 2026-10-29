<?php

use App\Enums\Language;
use App\Enums\PropertyStatus;
use App\Filament\Resources\Amenities\Pages\ManageAmenities;
use App\Filament\Resources\Cities\Pages\ManageCities;
use App\Filament\Resources\Inquiries\Pages\ManageInquiries;
use App\Filament\Resources\Owners\Pages\ListOwners;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\PropertyTypes\Pages\ManagePropertyTypes;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\InquiriesBySourceChart;
use App\Filament\Widgets\InquiriesPerMonthChart;
use App\Filament\Widgets\InquiriesToAnswer;
use App\Filament\Widgets\ListingsToFix;
use App\Filament\Widgets\StaleListings;
use App\Models\Amenity;
use App\Models\Inquiry;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\PropertyTypeSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the language is switched from the user menu and saved on the account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/inquiries')
        ->get('/language/ar')
        ->assertRedirect('/inquiries');

    expect($user->fresh()->locale)->toBe(Language::Arabic);
});

test('only English and Arabic can be chosen', function () {
    $this->actingAs(User::factory()->create())
        ->get('/language/fr')
        ->assertNotFound();
});

test('guests cannot switch the language', function () {
    $this->get('/language/ar')->assertRedirect('/login');
});

test('the panel is in English by default and offers Arabic', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('dir="ltr"', escape: false)
        ->assertSee('العربية');
});

test('the panel is right to left in Arabic and offers English', function () {
    $this->actingAs(User::factory()->create(['locale' => Language::Arabic]))
        ->get('/')
        ->assertOk()
        ->assertSee('dir="rtl"', escape: false)
        ->assertSee(__('Settings', locale: 'ar'))
        ->assertSee('English');
});

test('validation messages are in Arabic for an Arabic-speaking admin', function () {
    $this->actingAs(User::factory()->create(['locale' => Language::Arabic]));
    app()->setLocale(Language::Arabic->value);

    $component = Livewire::test(ManageCities::class)
        ->callAction('create', data: ['name_en' => null, 'name_ar' => 'Skoura'])
        ->assertHasFormErrors(['name_en' => 'required', 'name_ar']);

    expect($component->errors()->first('mountedActions.0.data.name_en'))->toBe('حقل الاسم (بالإنجليزية) إلزامي.')
        ->and($component->errors()->first('mountedActions.0.data.name_ar'))->toBe('يجب كتابة الاسم (بالعربية) باللغة العربية.');
});

test('every admin page and form is translated into Arabic', function () {
    $this->seed([PropertyTypeSeeder::class, CitySeeder::class, AmenitySeeder::class]);
    $owner = Owner::factory()->create();
    $property = Property::factory()
        ->for($owner)
        ->has(PropertyImage::factory(), 'images')
        ->hasAttached(Amenity::first())
        ->create(['status' => PropertyStatus::Published, 'published_at' => now()->subDays(120)]);
    Inquiry::factory()->for($property)->create(['created_at' => now()->subDays(2)]);

    $this->actingAs(User::factory()->create(['locale' => Language::Arabic]));
    app()->setLocale(Language::Arabic->value);

    /** @var Collection<int, string> $untranslated */
    $untranslated = collect();

    Lang::handleMissingKeysUsing(function (string $key) use ($untranslated): string {
        // Package keys (filament-panels::…), Laravel group keys (validation.…), deliberately empty labels
        // and labels that are already Arabic are not ours to translate.
        if (filled($key) && ! str_contains($key, '::') && ! preg_match('/^[a-z_]+\.[a-z_.]+$/', $key) && ! preg_match('/\p{Arabic}/u', $key)) {
            $untranslated->push($key);
        }

        return $key;
    });

    foreach (['/', '/properties', '/properties/create', "/properties/{$property->id}", "/properties/{$property->id}/edit", '/inquiries', '/owners', "/owners/{$owner->id}", '/cities', '/property-types', '/amenities', '/users', '/profile'] as $url) {
        $this->get($url)->assertOk();
    }

    foreach ([DashboardStats::class, InquiriesToAnswer::class, InquiriesPerMonthChart::class, InquiriesBySourceChart::class, ListingsToFix::class, StaleListings::class] as $widget) {
        Livewire::test($widget);
    }

    foreach ([ManageInquiries::class, ListOwners::class, ManageCities::class, ManagePropertyTypes::class, ManageAmenities::class, ManageUsers::class] as $page) {
        Livewire::test($page)->mountAction('create');
    }

    Livewire::test(ListProperties::class)->mountAction(TestAction::make('markAsSoldOrRented')->table($property));

    expect($untranslated->unique()->sort()->values()->all())->toBe([]);
});
