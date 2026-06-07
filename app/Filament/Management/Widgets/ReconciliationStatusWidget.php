<?php

namespace App\Filament\Management\Widgets;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReconciliationStatusWidget extends BaseWidget
{
    protected ?string $heading = 'Deposit Reconciliation Status';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();

        $base = Deposit::visibleTo($user);

        $unreconciled = (clone $base)->where('status', DepositStatus::Unreconciled->value)->count();
        $partial = (clone $base)->where('status', DepositStatus::PartiallyReconciled->value)->count();
        $reconciled = (clone $base)->where('status', DepositStatus::Reconciled->value)->count();
        $disputed = (clone $base)->where('status', DepositStatus::Disputed->value)->count();

        return [
            Stat::make('Unreconciled', $unreconciled)
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Partially Reconciled', $partial)
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
            Stat::make('Reconciled', $reconciled)
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Disputed', $disputed)
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($disputed > 0 ? 'danger' : 'gray'),
        ];
    }
}
