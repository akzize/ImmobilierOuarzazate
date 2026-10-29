<?php

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\Language;
use App\Enums\PropertyStatus;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\InquiriesBySourceChart;
use App\Filament\Widgets\InquiriesPerMonthChart;
use App\Filament\Widgets\InquiriesToAnswer;
use App\Filament\Widgets\ListingsToFix;
use App\Filament\Widgets\StaleListings;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Livewire\invade;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @return array<string, Stat>
 */
function dashboardStats(): array
{
    return collect(invade(Livewire::test(DashboardStats::class)->instance())->getStats())
        ->keyBy(fn (Stat $stat): string => (string) $stat->getLabel())
        ->all();
}

test('the dashboard shows every widget', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeLivewire(DashboardStats::class)
        ->assertSeeLivewire(InquiriesToAnswer::class)
        ->assertSeeLivewire(InquiriesPerMonthChart::class)
        ->assertSeeLivewire(InquiriesBySourceChart::class)
        ->assertSeeLivewire(ListingsToFix::class)
        ->assertSeeLivewire(StaleListings::class);
});

test('the stats count active listings, new inquiries and inquiries waiting for a reply', function () {
    Property::factory()->count(2)->published()->create();
    Property::factory()->create(['status' => PropertyStatus::Reserved]);
    Property::factory()->create();

    Inquiry::factory()->count(3)->create();
    Inquiry::factory()->create(['created_at' => now()->subDays(10)]);

    $stats = dashboardStats();

    expect($stats['Active listings']->getValue())->toBe(3)
        ->and($stats['New inquiries (7 days)']->getValue())->toBe(3)
        ->and($stats['New inquiries (7 days)']->getDescription())->toBe('2 more than the week before')
        ->and($stats['Waiting for a reply over 24h']->getValue())->toBe(1)
        ->and($stats['Waiting for a reply over 24h']->getColor())->toBe('danger');
});

test('the stats show the conversion rate, the first reply time and the share of English inquiries', function () {
    $this->travelTo($receivedAt = now()->subDays(5)->startOfHour());
    $won = Inquiry::factory()->create(['lang' => Language::English]);
    $lost = Inquiry::factory()->create();

    $this->travelTo($receivedAt->copy()->addHours(2));
    $won->update(['status' => InquiryStatus::Won]);
    $lost->update(['status' => InquiryStatus::Lost]);
    $this->travelBack();

    Inquiry::factory()->count(2)->create();

    $stats = dashboardStats();

    expect($stats['Conversion rate']->getValue())->toBe('50%')
        ->and($stats['Average first reply']->getValue())->toBe('2h')
        ->and($stats['Inquiries in English']->getValue())->toBe('25%');
});

test('inquiries to answer lists new inquiries oldest first and marks them as contacted', function () {
    $older = Inquiry::factory()->create(['created_at' => now()->subDays(2)]);
    $newer = Inquiry::factory()->create();
    $contacted = Inquiry::factory()->create(['status' => InquiryStatus::Contacted]);

    Livewire::test(InquiriesToAnswer::class)
        ->assertCanSeeTableRecords([$older, $newer], inOrder: true)
        ->assertCanNotSeeTableRecords([$contacted])
        ->callAction(TestAction::make('markAsContacted')->table($older))
        ->assertCanNotSeeTableRecords([$older]);

    expect($older->fresh()->status)->toBe(InquiryStatus::Contacted);
});

test('listings to improve shows published listings missing a translation, photos or a map location', function () {
    $complete = Property::factory()
        ->published()
        ->withEnglish()
        ->has(PropertyImage::factory()->count(ListingsToFix::RECOMMENDED_PHOTOS), 'images')
        ->create(['latitude' => 30.9189, 'longitude' => -6.8934]);
    $incomplete = Property::factory()->published()->create();
    $draft = Property::factory()->create();

    Livewire::test(ListingsToFix::class)
        ->assertCanSeeTableRecords([$incomplete])
        ->assertCanNotSeeTableRecords([$complete, $draft])
        ->assertTableColumnStateSet('problems', ['English translation', 'Photos (5+)', 'Map location'], $incomplete);
});

test('stale listings are old listings nobody asked about lately', function () {
    $stale = Property::factory()->published()->create(['published_at' => now()->subDays(120)]);
    $askedAboutRecently = Property::factory()->published()->create(['published_at' => now()->subDays(120)]);
    Inquiry::factory()->for($askedAboutRecently)->create(['created_at' => now()->subDays(10)]);
    $recent = Property::factory()->published()->create(['published_at' => now()->subDays(20)]);

    Livewire::test(StaleListings::class)
        ->assertCanSeeTableRecords([$stale])
        ->assertCanNotSeeTableRecords([$askedAboutRecently, $recent]);
});

test('the monthly chart counts inquiries over the last twelve months', function () {
    $this->travelTo(Carbon::parse('2026-10-15 10:00'));
    Inquiry::factory()->count(2)->create(['created_at' => '2026-10-02 09:00']);
    Inquiry::factory()->create(['created_at' => '2026-08-20 09:00']);
    Inquiry::factory()->create(['created_at' => '2025-10-31 09:00']);

    $data = invade(Livewire::test(InquiriesPerMonthChart::class)->instance())->getData();

    expect($data['labels'])->toHaveCount(12)
        ->and($data['labels'][0])->toBe('Nov 2025')
        ->and($data['labels'][11])->toBe('Oct 2026')
        ->and($data['datasets'][0]['data'])->toBe([0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 2]);
});

test('the source chart ranks channels from most to fewest inquiries', function () {
    Inquiry::factory()->count(3)->create(['source' => InquirySource::WhatsApp]);
    Inquiry::factory()->create(['source' => InquirySource::Phone]);

    $data = invade(Livewire::test(InquiriesBySourceChart::class)->instance())->getData();

    expect(array_slice($data['labels'], 0, 2))->toBe(['WhatsApp', 'Phone call'])
        ->and(array_slice($data['datasets'][0]['data'], 0, 3))->toBe([3, 1, 0]);
});
