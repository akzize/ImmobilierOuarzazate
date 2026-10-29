<?php

use App\Enums\InquirySource;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use App\Models\User;
use App\Notifications\NewInquiryReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('every admin is alerted when an inquiry arrives from the website', function (InquirySource $source) {
    Notification::fake();
    $admins = User::factory()->count(2)->create();

    $inquiry = Inquiry::factory()->create(['source' => $source]);

    Notification::assertSentTo(
        $admins,
        NewInquiryReceived::class,
        fn (NewInquiryReceived $notification, array $channels): bool => $notification->inquiry->is($inquiry) && $channels === ['database', 'mail'],
    );
})->with([InquirySource::Form, InquirySource::VisitForm]);

test('inquiries logged by an admin do not trigger an alert', function () {
    Notification::fake();
    User::factory()->create();

    Inquiry::factory()->create(['source' => InquirySource::Phone]);

    Notification::assertNothingSent();
});

test('the alert links to the new inquiries and keeps visitor text out of the email body', function () {
    $admin = User::factory()->create();
    $inquiry = Inquiry::factory()->general()->create([
        'full_name' => 'Salma Bennani',
        'message' => '[Click here](https://phishing.example)',
    ]);

    $notification = new NewInquiryReceived($inquiry);
    $mail = $notification->toMail($admin);
    $panelNotification = $notification->toDatabase($admin);

    expect($admin->notifications()->count())->toBe(1)
        ->and($mail->subject)->toBe('New inquiry from Salma Bennani')
        ->and($mail->introLines)->toBe(['A new inquiry arrived from the website: General inquiry.'])
        ->and($mail->actionUrl)->toBe(InquiryResource::getUrl('index', ['tab' => 'new']))
        ->and($panelNotification['title'])->toBe('New inquiry from Salma Bennani');
});
