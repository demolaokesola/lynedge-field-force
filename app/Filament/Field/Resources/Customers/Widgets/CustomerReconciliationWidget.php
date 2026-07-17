<?php

namespace App\Filament\Field\Resources\Customers\Widgets;

use App\Enums\DepositStatus;
use App\Enums\DistributionStatus;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Distribution;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerReconciliationWidget extends BaseWidget
{
    public ?Customer $record = null;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null || $this->record === null) {
            return [
                Stat::make('Distributed (₦)', '—'),
                Stat::make('Deposited (₦)', '—'),
                Stat::make('Outstanding Balance (₦)', '—'),
            ];
        }

        $distributionTotal = (float) Distribution::visibleTo($user)
            ->where('customer_id', $this->record->id)
            ->where('status', DistributionStatus::Posted->value)
            ->sum('total_amount');

        $depositBase = Deposit::visibleTo($user)->where('customer_id', $this->record->id);

        $depositTotal = (float) (clone $depositBase)->sum('amount');
        $reconciled = (float) (clone $depositBase)->where('status', DepositStatus::Reconciled->value)->sum('amount');
        $unreconciled = $depositTotal - $reconciled;
        $outstanding = $distributionTotal - $depositTotal;

        return [
            Stat::make('Distributed (₦)', number_format($distributionTotal, 2))
                ->description('Posted distributions')
                ->color('primary'),
            Stat::make('Deposited (₦)', number_format($depositTotal, 2))
                ->description('Reconciled: ₦'.number_format($reconciled, 2).' · Unreconciled: ₦'.number_format($unreconciled, 2))
                ->color('info'),
            Stat::make('Outstanding Balance (₦)', number_format($outstanding, 2))
                ->description($outstanding > 0 ? 'Owed by customer' : 'Fully covered')
                ->color($outstanding > 0 ? 'danger' : 'success'),
        ];
    }
}
