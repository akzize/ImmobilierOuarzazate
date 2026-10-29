<?php

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the first reply and the closing date are stamped from the status', function () {
    $inquiry = Inquiry::factory()->create();

    expect($inquiry->contacted_at)->toBeNull()
        ->and($inquiry->closed_at)->toBeNull();

    $this->travelTo($contactedAt = now()->addHour()->startOfSecond());
    $inquiry->update(['status' => InquiryStatus::Contacted]);

    $this->travelTo($closedAt = now()->addDays(2)->startOfSecond());
    $inquiry->update(['status' => InquiryStatus::VisitScheduled]);
    $inquiry->update(['status' => InquiryStatus::Won]);

    $inquiry->refresh();

    expect($inquiry->contacted_at->equalTo($contactedAt))->toBeTrue()
        ->and($inquiry->closed_at->equalTo($closedAt))->toBeTrue();
});

test('reopening a closed inquiry clears its closing date', function () {
    $inquiry = Inquiry::factory()->create(['status' => InquiryStatus::Lost]);

    expect($inquiry->closed_at)->not->toBeNull();

    $inquiry->update(['status' => InquiryStatus::Contacted]);

    expect($inquiry->fresh()->closed_at)->toBeNull();
});

test('WhatsApp links use the international number', function (string $phone, ?string $url) {
    expect((new Inquiry(['phone' => $phone]))->whatsappUrl())->toBe($url);
})->with([
    'local mobile' => ['06 12 34 56 78', 'https://wa.me/212612345678'],
    'with +212' => ['+212 6 12 34 56 78', 'https://wa.me/212612345678'],
    'with 00212' => ['00212612345678', 'https://wa.me/212612345678'],
    'foreign number' => ['+33 6 12 34 56 78', 'https://wa.me/33612345678'],
    'too short' => ['12345', null],
]);
