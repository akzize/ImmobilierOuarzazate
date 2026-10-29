<?php

namespace App\Filament\Resources\Inquiries\Actions;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Inquiry actions shared by the Inquiries resource and the listing's Inquiries tab.
 */
class InquiryActions
{
    /**
     * One quick action per status; the inquiry's current status is hidden.
     *
     * @return array<Action>
     */
    public static function changeStatus(): array
    {
        return array_map(self::changeStatusTo(...), InquiryStatus::cases());
    }

    public static function whatsApp(): Action
    {
        return Action::make('whatsApp')
            ->label(__('WhatsApp'))
            ->icon(Heroicon::OutlinedChatBubbleOvalLeftEllipsis)
            ->color('success')
            ->url(fn (Inquiry $record): ?string => $record->whatsappUrl(), shouldOpenInNewTab: true)
            ->visible(fn (Inquiry $record): bool => $record->whatsappUrl() !== null);
    }

    /**
     * Closing (won or lost) asks for confirmation; the other moves are one click.
     */
    private static function changeStatusTo(InquiryStatus $status): Action
    {
        return Action::make(Str::camel('mark_as_'.$status->value))
            ->label($status->getActionLabel())
            ->icon($status->getIcon())
            ->color($status->getColor())
            ->requiresConfirmation($status->isClosed())
            ->visible(fn (Inquiry $record): bool => $record->status !== $status)
            ->action(fn (Inquiry $record): bool => $record->update(['status' => $status]))
            ->successNotificationTitle(__('Inquiry marked as :status', ['status' => $status->getLabel()]));
    }
}
