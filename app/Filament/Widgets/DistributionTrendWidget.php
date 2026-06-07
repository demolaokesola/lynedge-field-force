<?php

namespace App\Filament\Widgets;

use App\Enums\DistributionStatus;
use App\Models\Distribution;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DistributionTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Distribution Value Trend (Last 12 Months)';

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['superuser', 'platform_admin']) ?? false;
    }

    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(fn (int $i): Carbon => now()->startOfMonth()->subMonths($i));

        $totals = Distribution::query()
            ->where('status', DistributionStatus::Posted->value)
            ->whereDate('invoice_date', '>=', $months->first())
            ->selectRaw("DATE_TRUNC('month', invoice_date) AS month, SUM(total_amount) AS total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->mapWithKeys(fn ($v, string $k): array => [Carbon::parse($k)->format('Y-m') => $v]);

        $labels = $months->map(fn (Carbon $m): string => $m->format('M Y'))->all();
        $data = $months->map(fn (Carbon $m): float => (float) ($totals[$m->format('Y-m')] ?? 0))->all();

        return [
            'datasets' => [
                [
                    'label' => 'Distribution Value (₦)',
                    'data' => $data,
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
