<?php

namespace App\Notifications;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every admin when an inquiry arrives from the website: in the panel's notification bell and by email.
 * The email leaves out the visitor's name and message on purpose: they are free text, and Markdown links in them would render as real links.
 */
class NewInquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Inquiry $inquiry) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('New inquiry from :name', ['name' => $this->inquiry->full_name]))
            ->body($this->listingLabel())
            ->icon(InquiryStatus::New->getIcon())
            ->iconColor(InquiryStatus::New->getColor())
            ->actions([
                Action::make('open')
                    ->label(__('Open new inquiries'))
                    ->url(self::newInquiriesUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New inquiry from :name', ['name' => $this->inquiry->full_name]))
            ->line(__('A new inquiry arrived from the website: :listing.', ['listing' => $this->listingLabel()]))
            ->action(__('Open new inquiries'), self::newInquiriesUrl());
    }

    private function listingLabel(): string
    {
        return $this->inquiry->property?->reference ?? __('General inquiry');
    }

    private static function newInquiriesUrl(): string
    {
        return InquiryResource::getUrl('index', ['tab' => InquiryStatus::New->value]);
    }
}
