<?php

namespace App\Filament\Field\Resources\Customers\Widgets;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Distribution;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerActivityWidget extends BaseWidget
{
    public ?Customer $record = null;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null || $this->record === null) {
            return [
                Stat::make('Last Distribution', '—'),
                Stat::make('Last Deposit', '—'),
                Stat::make('Days Since Last Activity', '—'),
            ];
        }

        $lastDistributionAt = Distribution::visibleTo($user)
            ->where('customer_id', $this->record->id)
            ->latest('invoice_date')
            ->latest('created_at')
            ->value('created_at');

        $lastDepositAt = Deposit::visibleTo($user)
            ->where('customer_id', $this->record->id)
            ->latest('deposit_date')
            ->latest('created_at')
            ->value('created_at');

        $lastDistribution = $lastDistributionAt ? Carbon::parse($lastDistributionAt) : null;
        $lastDeposit = $lastDepositAt ? Carbon::parse($lastDepositAt) : null;

        $lastActivity = collect([$lastDistribution, $lastDeposit])
            ->filter()
            ->sortDesc()
            ->first();

        return [
            Stat::make('Last Distribution', $lastDistribution?->ago() ?? '—'),
            Stat::make('Last Deposit', $lastDeposit?->ago() ?? '—'),
            Stat::make('Days Since Last Activity', $lastActivity?->ago() ?? '—')
                ->color($lastActivity !== null && $lastActivity->diffInDays(now()) > 30 ? 'warning' : 'success'),
        ];
    }
}
