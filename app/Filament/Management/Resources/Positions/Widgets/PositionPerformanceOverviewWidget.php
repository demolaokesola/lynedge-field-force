<?php

namespace App\Filament\Management\Resources\Positions\Widgets;

use App\Enums\DistributionStatus;
use App\Models\Cycle;
use App\Models\Deposit;
use App\Models\Position;
use App\Models\RepMonthlyTarget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Target/Deposits figures are attributed to the position's CURRENT occupant only
 * (mirrors AttainmentLeaderboardWidget's simplification) — a prior occupant's activity
 * before a reassignment is not reflected here.
 */
class PositionPerformanceOverviewWidget extends BaseWidget
{
    public ?Position $record = null;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $cycle = Cycle::where('is_current', true)->first();
        $occupantId = $this->record?->openAssignment?->user_id;

        if ($this->record === null || $cycle === null) {
            return [
                Stat::make('Target YTD (₦, Current Occupant)', '—'),
                Stat::make('Distribution Value YTD (₦)', '—'),
                Stat::make('Deposits YTD (₦, Current Occupant)', '—'),
                Stat::make('Attainment %', '—'),
            ];
        }

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

        $attainmentPct = $targetValueYtd > 0 ? round($distributionValueYtd / $targetValueYtd * 100, 1) : null;

        return [
            Stat::make('Target YTD (₦, Current Occupant)', number_format($targetValueYtd, 2)),
            Stat::make('Distribution Value YTD (₦)', number_format($distributionValueYtd, 2)),
            Stat::make('Deposits YTD (₦, Current Occupant)', number_format($depositsYtd, 2)),
            Stat::make('Attainment %', $attainmentPct === null ? '—' : number_format($attainmentPct, 1).'%')
                ->color(match (true) {
                    $attainmentPct === null => 'gray',
                    $attainmentPct >= 100 => 'success',
                    $attainmentPct >= 75 => 'warning',
                    default => 'danger',
                }),
        ];
    }
}
