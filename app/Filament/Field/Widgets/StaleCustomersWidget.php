<?php

namespace App\Filament\Field\Widgets;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Distribution;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StaleCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $days = config('field_dashboard.stale_customer_days');
        $cutoff = now()->subDays($days)->toDateString();

        $lastDistribution = Distribution::visibleTo($user)
            ->groupBy('customer_id')
            ->selectRaw('customer_id, MAX(invoice_date) as last_date');

        $lastDeposit = Deposit::visibleTo($user)
            ->groupBy('customer_id')
            ->selectRaw('customer_id, MAX(deposit_date) as last_date');

        return $table
            ->heading("Customers Needing a Visit ({$days}+ Days Inactive)")
            ->query(
                Customer::visibleTo($user)
                    ->leftJoinSub($lastDistribution, 'ld', 'ld.customer_id', 'customers.id')
                    ->leftJoinSub($lastDeposit, 'lp', 'lp.customer_id', 'customers.id')
                    ->whereRaw("COALESCE(GREATEST(ld.last_date, lp.last_date), '1900-01-01') < ?", [$cutoff])
                    ->select('customers.*')
                    ->selectRaw('GREATEST(ld.last_date, lp.last_date) as last_activity_at')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('territory.name')
                    ->label('Territory'),
                TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->date()
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->defaultSort('last_activity_at', 'asc')
            ->paginated([5, 10, 25]);
    }
}
