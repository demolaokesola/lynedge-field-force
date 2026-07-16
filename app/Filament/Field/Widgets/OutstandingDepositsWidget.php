<?php

namespace App\Filament\Field\Widgets;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OutstandingDepositsWidget extends BaseWidget
{
    // protected ?string $heading = 'Outstanding Deposits';

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

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

        return [
            Stat::make('Unreconciled', $count)
                ->description('Pending + partial')
                ->descriptionIcon('heroicon-m-clock')
                ->color($count > 0 ? 'warning' : 'success'),
            Stat::make('Outstanding Value (₦)', number_format((float) $total, 2))
                ->description('Sum of unreconciled deposits')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
            Stat::make('Disputed', $disputed)
                ->description('Flagged for review')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($disputed > 0 ? 'danger' : 'success'),
        ];
    }
}
