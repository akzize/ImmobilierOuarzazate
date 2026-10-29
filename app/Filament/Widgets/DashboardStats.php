<?php

namespace App\Filament\Widgets;

use App\Enums\InquiryStatus;
use App\Enums\Language;
use App\Enums\PropertyStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Inquiry;
use App\Models\Property;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Headline numbers. Grouping happens in PHP so the queries work the same on MySQL and on the SQLite test database.
 */
class DashboardStats extends StatsOverviewWidget
{
    /**
     * Window for the conversion, reply-time and language figures.
     */
    public const int RECENT_DAYS = 90;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            $this->activeListings(),
            $this->newInquiries(),
            $this->awaitingReply(),
            $this->conversionRate(),
            $this->firstReplyTime(),
            $this->englishShare(),
        ];
    }

    private function activeListings(): Stat
    {
        $published = Property::query()->where('status', PropertyStatus::Published)->count();
        $reserved = Property::query()->where('status', PropertyStatus::Reserved)->count();

        return Stat::make(__('Active listings'), $published + $reserved)
            ->description(__(':published published · :reserved reserved', ['published' => $published, 'reserved' => $reserved]))
            ->url(PropertyResource::getUrl('index', ['tab' => PropertyStatus::Published->value]));
    }

    /**
     * Last 7 days against the 7 before, with a daily sparkline over both weeks.
     */
    private function newInquiries(): Stat
    {
        $countsByDate = Inquiry::query()
            ->where('created_at', '>=', today()->subDays(13))
            ->pluck('created_at')
            ->countBy(fn (CarbonInterface $createdAt): string => $createdAt->toDateString());

        $dailyCounts = collect(range(13, 0))
            ->map(fn (int $daysAgo): int => $countsByDate->get(today()->subDays($daysAgo)->toDateString(), 0));

        $thisWeek = $dailyCounts->slice(7)->sum();
        $difference = $thisWeek - $dailyCounts->take(7)->sum();

        return Stat::make(__('New inquiries (7 days)'), $thisWeek)
            ->description(match (true) {
                $difference > 0 => __(':count more than the week before', ['count' => $difference]),
                $difference < 0 => __(':count fewer than the week before', ['count' => abs($difference)]),
                default => __('Same as the week before'),
            })
            ->descriptionIcon($difference < 0 ? Heroicon::ArrowTrendingDown : Heroicon::ArrowTrendingUp)
            ->color($difference < 0 ? 'danger' : 'success')
            ->chart($dailyCounts->all());
    }

    private function awaitingReply(): Stat
    {
        $waiting = Inquiry::query()
            ->where('status', InquiryStatus::New)
            ->where('created_at', '<', now()->subDay())
            ->count();

        return Stat::make(__('Waiting for a reply over 24h'), $waiting)
            ->description($waiting > 0 ? __('Answer these first') : __('All caught up'))
            ->descriptionIcon($waiting > 0 ? Heroicon::ExclamationTriangle : Heroicon::CheckCircle)
            ->color($waiting > 0 ? 'danger' : 'success')
            ->url(InquiryResource::getUrl('index', ['tab' => InquiryStatus::New->value]));
    }

    /**
     * Won out of all inquiries closed in the window.
     */
    private function conversionRate(): Stat
    {
        $closedStatuses = Inquiry::query()
            ->whereIn('status', [InquiryStatus::Won, InquiryStatus::Lost])
            ->where('closed_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->pluck('status');

        $won = $closedStatuses->filter(fn (InquiryStatus $status): bool => $status === InquiryStatus::Won)->count();
        $closed = $closedStatuses->count();

        return Stat::make(__('Conversion rate'), $closed > 0 ? round($won / $closed * 100).'%' : '—')
            ->description(__(':won won of :closed closed · last :days days', ['won' => $won, 'closed' => $closed, 'days' => self::RECENT_DAYS]));
    }

    /**
     * Average time between receiving an inquiry and its first reply.
     */
    private function firstReplyTime(): Stat
    {
        $minutesToReply = Inquiry::query()
            ->whereNotNull('contacted_at')
            ->where('created_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->get(['created_at', 'contacted_at'])
            ->map(fn (Inquiry $inquiry): float => $inquiry->created_at->diffInMinutes($inquiry->contacted_at));

        $averageReply = $minutesToReply->isEmpty()
            ? '—'
            : CarbonInterval::minutes((int) round($minutesToReply->avg()))->cascade()->forHumans(short: true, parts: 2);

        return Stat::make(__('Average first reply'), $averageReply)
            ->description(__(':count inquiries answered · last :days days', ['count' => $minutesToReply->count(), 'days' => self::RECENT_DAYS]));
    }

    /**
     * Share of inquiries written in English: tells whether the English translations are worth the effort.
     */
    private function englishShare(): Stat
    {
        $languages = Inquiry::query()
            ->where('created_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->pluck('lang');

        $english = $languages->filter(fn (Language $language): bool => $language === Language::English)->count();

        return Stat::make(__('Inquiries in English'), $languages->isNotEmpty() ? round($english / $languages->count() * 100).'%' : '—')
            ->description(__(':english of :total · last :days days', ['english' => $english, 'total' => $languages->count(), 'days' => self::RECENT_DAYS]));
    }
}
