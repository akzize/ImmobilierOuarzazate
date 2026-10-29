<?php

namespace App\Filament\Widgets;

use App\Enums\InquirySource;
use App\Models\Inquiry;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Which channels bring leads: horizontal bars sorted from most to fewest, in the panel's primary colour.
 */
class InquiriesBySourceChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $maxHeight = '260px';

    public function getHeading(): string|Htmlable|null
    {
        return __('Inquiries by source');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('Last :count months', ['count' => InquiriesPerMonthChart::MONTHS]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $countsBySource = Inquiry::query()
            ->where('created_at', '>=', today()->subMonths(InquiriesPerMonthChart::MONTHS))
            ->pluck('source')
            ->countBy(fn (InquirySource $source): string => $source->value);

        $sources = collect(InquirySource::cases())
            ->sortByDesc(fn (InquirySource $source): int => $countsBySource->get($source->value, 0))
            ->values();

        return [
            'datasets' => [
                [
                    'label' => __('Inquiries'),
                    'data' => $sources->map(fn (InquirySource $source): int => $countsBySource->get($source->value, 0))->all(),
                    'borderRadius' => 4,
                    'maxBarThickness' => 24,
                ],
            ],
            'labels' => $sources->map(fn (InquirySource $source): string => $source->getLabel())->all(),
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
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
