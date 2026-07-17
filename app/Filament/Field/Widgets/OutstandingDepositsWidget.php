<?php

namespace App\Filament\Field\Widgets;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OutstandingDepositsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return auth()->user()?->hasRole('supervisor')
            ? 'Region Outstanding Deposits'
            : 'My Outstanding Deposits';
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $base = Deposit::visibleTo($user)
            ->whereIn('status', [
                DepositStatus::Unreconciled->value,
                DepositStatus::PartiallyReconciled->value,
            ]);

        $count = (clone $base)->count();
        $total = (clone $base)->sum('amount');

        $disputed = Deposit::visibleTo($user)
            ->where('status', DepositStatus::Disputed->value)
            ->count();

        $unreconciledThreshold = config('field_dashboard.deposit_alerts.unreconciled_count');
        $disputedThreshold = config('field_dashboard.deposit_alerts.disputed_count');
        $valueThreshold = config('field_dashboard.deposit_alerts.outstanding_value');

        return [
            Stat::make('Unreconciled', $count)
                ->description('Pending + partial')
                ->descriptionIcon('heroicon-m-clock')
                ->color(match (true) {
                    $count === 0 => 'success',
                    $count > $unreconciledThreshold => 'danger',
                    default => 'warning',
                }),
            Stat::make('Outstanding Value (₦)', number_format((float) $total, 2))
                ->description($total > $valueThreshold ? 'Above alert threshold — needs attention' : 'Sum of unreconciled deposits')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($total > $valueThreshold ? 'danger' : 'info'),
            Stat::make('Disputed', $disputed)
                ->description('Flagged for review')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($disputed >= $disputedThreshold ? 'danger' : 'success'),
        ];
    }
}
