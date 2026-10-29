<?php

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Inquiries\Pages\ManageInquiries;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the status tabs show their counts and filter the list', function () {
    $newInquiries = Inquiry::factory()->count(2)->create();
    $wonInquiry = Inquiry::factory()->create(['status' => InquiryStatus::Won]);

    $component = Livewire::test(ManageInquiries::class);
    $tabs = $component->instance()->getTabs();

    expect($tabs['all']->getBadge())->toBe('3')
        ->and($tabs['new']->getBadge())->toBe('2')
        ->and($tabs['won']->getBadge())->toBe('1')
        ->and($tabs['lost']->getBadge())->toBeNull();

    $component->set('activeTab', 'new')
        ->assertCanSeeTableRecords($newInquiries)
        ->assertCanNotSeeTableRecords([$wonInquiry]);
});

test('the navigation badge counts new inquiries', function () {
    Inquiry::factory()->count(2)->create();
    Inquiry::factory()->create(['status' => InquiryStatus::Contacted]);

    expect(InquiryResource::getNavigationBadge())->toBe('2');
});

test('a phone call is logged as a general inquiry by default', function () {
    Livewire::test(ManageInquiries::class)
        ->callAction('create', data: [
            'full_name' => 'Youssef Amrani',
            'phone' => '0612345678',
            'message' => 'Looking for a house to rent near the kasbah.',
        ])
        ->assertHasNoFormErrors();

    expect(Inquiry::sole())
        ->property_id->toBeNull()
        ->source->toBe(InquirySource::Phone)
        ->status->toBe(InquiryStatus::New);
});

test('an inquiry is logged against a listing', function () {
    $property = Property::factory()->create();

    Livewire::test(ManageInquiries::class)
        ->callAction('create', data: [
            'property_id' => $property->id,
            'full_name' => 'Fatima Zahra',
            'phone' => '0612345678',
            'source' => InquirySource::WalkIn->value,
            'message' => 'Came by the agency to ask about this listing.',
        ])
        ->assertHasNoFormErrors();

    expect(Inquiry::sole())
        ->property_id->toBe($property->id)
        ->source->toBe(InquirySource::WalkIn);
});

test('quick actions move an inquiry through the pipeline', function () {
    $inquiry = Inquiry::factory()->create();

    Livewire::test(ManageInquiries::class)
        ->assertActionHidden(TestAction::make('markAsNew')->table($inquiry))
        ->callAction(TestAction::make('markAsContacted')->table($inquiry))
        ->assertNotified('Inquiry marked as Contacted')
        ->callAction(TestAction::make('markAsWon')->table($inquiry))
        ->assertNotified('Inquiry marked as Won');

    expect($inquiry->fresh())
        ->status->toBe(InquiryStatus::Won)
        ->contacted_at->not->toBeNull()
        ->closed_at->not->toBeNull();
});

test('the WhatsApp button opens a chat with the inquirer', function () {
    $inquiry = Inquiry::factory()->create(['phone' => '06 12 34 56 78']);

    Livewire::test(ManageInquiries::class)
        ->assertActionHasUrl(TestAction::make('whatsApp')->table($inquiry), 'https://wa.me/212612345678')
        ->assertActionShouldOpenUrlInNewTab(TestAction::make('whatsApp')->table($inquiry));
});
