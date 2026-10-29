<?php

namespace App\Filament\Widgets;

use App\Models\Inquiry;
use Carbon\CarbonInterface;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * One bar per month over the last year, in the panel's primary colour (a single series needs no legend).
 */
class InquiriesPerMonthChart extends ChartWidget
{
    public const int MONTHS = 12;

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '260px';

    public function getHeading(): string|Htmlable|null
    {
        return __('Inquiries per month');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('Last :count months', ['count' => self::MONTHS]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $months = collect(range(self::MONTHS - 1, 0))
            ->map(fn (int $monthsAgo): CarbonInterface => today()->startOfMonth()->subMonths($monthsAgo));

        $countsByMonth = Inquiry::query()
            ->where('created_at', '>=', $months->first())
            ->pluck('created_at')
            ->countBy(fn (CarbonInterface $createdAt): string => $createdAt->format('Y-m'));

        return [
            'datasets' => [
                [
                    'label' => __('Inquiries'),
                    'data' => $months->map(fn (CarbonInterface $month): int => $countsByMonth->get($month->format('Y-m'), 0))->all(),
                    'borderRadius' => 4,
                    'maxBarThickness' => 32,
                ],
            ],
            'labels' => $months->map(fn (CarbonInterface $month): string => $month->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
