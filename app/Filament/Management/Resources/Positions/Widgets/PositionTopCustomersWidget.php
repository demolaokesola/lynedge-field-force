<?php

namespace App\Filament\Management\Resources\Positions\Widgets;

use App\Models\Deposit;
use App\Models\Position;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Ranked by deposit value collected against this position's CURRENT occupant only
 * (see PositionPerformanceOverviewWidget for the occupant-attribution rationale).
 */
class PositionTopCustomersWidget extends BaseWidget
{
    public ?Position $record = null;

    protected static ?string $heading = 'Top Customers by Deposit Value (Current Occupant)';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $occupantId = $this->record?->openAssignment?->user_id;

        return $table
            ->query(
                Deposit::query()
                    ->join('customers', 'customers.id', 'deposits.customer_id')
                    ->where('deposits.user_id', $occupantId ?? 0)
                    ->groupBy('deposits.customer_id', 'customers.name')
                    ->select('deposits.customer_id as id', 'customers.name as customer_name')
                    ->selectRaw('SUM(deposits.amount) as total_deposit_value')
            )
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer'),
                TextColumn::make('total_deposit_value')
                    ->label('Deposit Value (₦)')
                    ->money('NGN')
                    ->sortable(),
            ])
            ->defaultSort('total_deposit_value', 'desc')
            ->defaultKeySort(false)
            ->paginated([5, 10, 25]);
    }
}
