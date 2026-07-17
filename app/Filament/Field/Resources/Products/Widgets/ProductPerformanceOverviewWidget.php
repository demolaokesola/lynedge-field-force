<?php

namespace App\Filament\Field\Resources\Products\Widgets;

use App\Enums\DistributionStatus;
use App\Models\Cycle;
use App\Models\DistributionLine;
use App\Models\Product;
use App\Models\RepMonthlyTarget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductPerformanceOverviewWidget extends BaseWidget
{
    public ?Product $record = null;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();
        $cycle = Cycle::where('is_current', true)->first();

        if ($user === null || $cycle === null || $this->record === null) {
            return [
                Stat::make('Target YTD (units)', '—'),
                Stat::make('Actual YTD (units)', '—'),
                Stat::make('Attainment %', '—'),
            ];
        }

        $asOf = now()->startOfMonth();

        $targetYtd = (float) RepMonthlyTarget::query()
            ->where('cycle_id', $cycle->id)
            ->where('user_id', $user->id)
            ->where('product_id', $this->record->id)
            ->where('year_month', '<=', $asOf)
            ->sum('target_qty');

        $actualYtd = (float) DistributionLine::query()
            ->join('distributions', 'distributions.id', 'distribution_lines.distribution_id')
            ->where('distributions.user_id', $user->id)
            ->where('distributions.status', DistributionStatus::Posted->value)
            ->where('distribution_lines.product_id', $this->record->id)
            ->whereBetween('distributions.invoice_date', [$cycle->starts_on, now()])
            ->sum('distribution_lines.quantity');

        $attainmentPct = $targetYtd > 0 ? round($actualYtd / $targetYtd * 100, 1) : null;

        return [
            Stat::make('Target YTD (units)', number_format($targetYtd, 2)),
            Stat::make('Actual YTD (units)', number_format($actualYtd, 2)),
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
