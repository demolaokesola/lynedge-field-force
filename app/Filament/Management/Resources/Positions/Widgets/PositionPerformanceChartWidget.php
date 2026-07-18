<?php

namespace App\Filament\Management\Resources\Positions\Widgets;

use App\Enums\DistributionStatus;
use App\Models\Cycle;
use App\Models\Deposit;
use App\Models\Position;
use App\Models\RepMonthlyTarget;
use Filament\Widgets\ChartWidget;

/**
 * Target/Deposits figures are attributed to the position's CURRENT occupant only
 * (see PositionPerformanceOverviewWidget) — a prior occupant's activity before a
 * reassignment is not reflected here.
 */
class PositionPerformanceChartWidget extends ChartWidget
{
    public ?Position $record = null;

    protected ?string $heading = 'Target vs Distribution vs Deposits (Current Cycle, ₦)';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $cycle = Cycle::where('is_current', true)->first();

        if ($this->record === null || $cycle === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $occupantId = $this->record->openAssignment?->user_id;
        $asOf = now()->startOfMonth();

        $targetValueYtd = $occupantId === null ? 0.0 : (float) RepMonthlyTarget::query()
            ->join('products', 'products.id', 'rep_monthly_targets.product_id')
            ->where('rep_monthly_targets.cycle_id', $cycle->id)
            ->where('rep_monthly_targets.user_id', $occupantId)
            ->where('rep_monthly_targets.year_month', '<=', $asOf)
            ->selectRaw('SUM(rep_monthly_targets.target_qty * products.unit_price) AS total')
            ->value('total') ?? 0.0;

        $distributionValueYtd = (float) $this->record->distributions()
            ->where('status', DistributionStatus::Posted->value)
            ->whereBetween('invoice_date', [$cycle->starts_on, now()])
            ->sum('total_amount');

        $depositsYtd = $occupantId === null ? 0.0 : (float) Deposit::query()
            ->where('user_id', $occupantId)
            ->whereBetween('deposit_date', [$cycle->starts_on, now()])
            ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Value (₦)',
                    'data' => [$targetValueYtd, $distributionValueYtd, $depositsYtd],
                    'backgroundColor' => [
                        'rgba(148, 163, 184, 0.6)',
                        'rgba(16, 185, 129, 0.6)',
                        'rgba(59, 130, 246, 0.6)',
                    ],
                ],
            ],
            'labels' => ['Target', 'Distribution Value', 'Deposits'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
