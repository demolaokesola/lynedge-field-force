<?php

namespace App\Filament\Widgets;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ReconciliationAgingWidget extends BaseWidget
{
    protected ?string $heading = 'Deposit Reconciliation Aging';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['superuser', 'accountant']) ?? false;
    }

    protected function getStats(): array
    {
        $statuses = [DepositStatus::Unreconciled->value, DepositStatus::PartiallyReconciled->value];

        $base = Deposit::query()->whereIn('status', $statuses);

        $now = Carbon::now();

        $bucket = fn (int $from, ?int $to): int => (clone $base)
            ->when(
                $to !== null,
                fn ($q) => $q->whereBetween('deposit_date', [$now->copy()->subDays($to), $now->copy()->subDays($from)]),
                fn ($q) => $q->where('deposit_date', '<', $now->copy()->subDays($from)),
            )
            ->count();

        return [
            Stat::make('0 – 7 days', $bucket(0, 7))
                ->description('Recent, likely in progress')
                ->color('success'),
            Stat::make('8 – 30 days', $bucket(8, 30))
                ->description('Needs attention soon')
                ->color('info'),
            Stat::make('31 – 90 days', $bucket(31, 90))
                ->description('Overdue')
                ->color('warning'),
            Stat::make('Over 90 days', $bucket(91, null))
                ->description('Significantly overdue')
                ->color('danger'),
        ];
    }
}
