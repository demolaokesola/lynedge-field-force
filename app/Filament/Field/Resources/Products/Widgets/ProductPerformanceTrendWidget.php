<?php

namespace App\Filament\Field\Resources\Products\Widgets;

use App\Enums\DistributionStatus;
use App\Models\Cycle;
use App\Models\DistributionLine;
use App\Models\Product;
use App\Models\RepMonthlyTarget;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ProductPerformanceTrendWidget extends ChartWidget
{
    public ?Product $record = null;

    protected ?string $heading = 'Monthly Target vs Actual (Current Cycle)';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $user = auth()->user();
        $cycle = Cycle::where('is_current', true)->first();

        if ($user === null || $cycle === null || $this->record === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $asOf = now()->startOfMonth();
        $months = collect();
        for ($month = $cycle->starts_on->copy()->startOfMonth(); $month->lte($asOf); $month->addMonth()) {
            $months->push($month->copy());
        }

        $targets = RepMonthlyTarget::query()
            ->where('cycle_id', $cycle->id)
            ->where('user_id', $user->id)
            ->where('product_id', $this->record->id)
            ->pluck('target_qty', 'year_month')
            ->mapWithKeys(fn ($qty, string $ym): array => [Carbon::parse($ym)->format('Y-m') => (float) $qty]);

        $actuals = DistributionLine::query()
            ->join('distributions', 'distributions.id', 'distribution_lines.distribution_id')
            ->where('distributions.user_id', $user->id)
            ->where('distributions.status', DistributionStatus::Posted->value)
            ->where('distribution_lines.product_id', $this->record->id)
            ->whereBetween('distributions.invoice_date', [$cycle->starts_on, now()])
            ->selectRaw("DATE_TRUNC('month', distributions.invoice_date) AS month, SUM(distribution_lines.quantity) AS total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->mapWithKeys(fn ($qty, string $ym): array => [Carbon::parse($ym)->format('Y-m') => (float) $qty]);

        $labels = $months->map(fn (Carbon $m): string => $m->format('M Y'))->all();

        return [
            'datasets' => [
                [
                    'label' => 'Target',
                    'data' => $months->map(fn (Carbon $m): float => $targets[$m->format('Y-m')] ?? 0)->all(),
                    'borderColor' => 'rgb(148, 163, 184)',
                    'backgroundColor' => 'rgba(148, 163, 184, 0.1)',
                    'borderDash' => [6, 4],
                    'fill' => false,
                ],
                [
                    'label' => 'Actual',
                    'data' => $months->map(fn (Carbon $m): float => $actuals[$m->format('Y-m')] ?? 0)->all(),
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
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
