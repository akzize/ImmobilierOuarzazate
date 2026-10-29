<?php

use App\Enums\InquiryStatus;
use App\Enums\Language;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Filament\Resources\Properties\RelationManagers\InquiriesRelationManager;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('only the inquiries of the listing are shown', function () {
    $property = Property::factory()->create();
    $inquiries = Inquiry::factory()->count(2)->for($property)->create();
    $otherListingInquiry = Inquiry::factory()->create();

    Livewire::test(InquiriesRelationManager::class, ['ownerRecord' => $property, 'pageClass' => ViewProperty::class])
        ->assertOk()
        ->assertCanSeeTableRecords($inquiries)
        ->assertCanNotSeeTableRecords([$otherListingInquiry]);
});

test('an inquiry is followed up from the view page', function () {
    $inquiry = Inquiry::factory()->create();

    Livewire::test(InquiriesRelationManager::class, ['ownerRecord' => $inquiry->property, 'pageClass' => ViewProperty::class])
        ->callAction(TestAction::make('edit')->table($inquiry), data: [
            'status' => InquiryStatus::Contacted->value,
            'admin_notes' => 'Called back, visit on Saturday',
        ])
        ->assertHasNoFormErrors();

    expect($inquiry->fresh())
        ->status->toBe(InquiryStatus::Contacted)
        ->admin_notes->toBe('Called back, visit on Saturday');
});

test('a phone inquiry is logged against the listing', function () {
    $property = Property::factory()->create();

    Livewire::test(InquiriesRelationManager::class, ['ownerRecord' => $property, 'pageClass' => ViewProperty::class])
        ->callAction(TestAction::make('create')->table(), data: [
            'full_name' => 'Fatima Zahra',
            'phone' => '+212612345678',
            'message' => 'Is the price negotiable?',
        ])
        ->assertHasNoFormErrors();

    expect($property->inquiries()->sole())
        ->full_name->toBe('Fatima Zahra')
        ->lang->toBe(Language::Arabic)
        ->status->toBe(InquiryStatus::New);
});

test('logging an inquiry requires a name, a phone and a message', function () {
    $property = Property::factory()->create();

    Livewire::test(InquiriesRelationManager::class, ['ownerRecord' => $property, 'pageClass' => ViewProperty::class])
        ->callAction(TestAction::make('create')->table(), data: [])
        ->assertHasFormErrors(['full_name' => 'required', 'phone' => 'required', 'message' => 'required']);

    expect($property->inquiries()->exists())->toBeFalse();
});
