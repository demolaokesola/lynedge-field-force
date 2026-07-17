<?php

namespace App\Filament\Field\Resources\Customers\Widgets;

use App\Models\Customer;
use App\Models\Deposit;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CustomerDepositsWidget extends BaseWidget
{
    public ?Customer $record = null;

    protected static ?string $heading = 'Deposits';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                Deposit::visibleTo($user)
                    ->where('customer_id', $this->record?->id)
                    ->latest('deposit_date')
            )
            ->columns([
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('deposit_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount (₦)')
                    ->sortable(),
                TextColumn::make('remaining_balance')
                    ->label('Balance (₦)')
                    ->state(fn (Deposit $record): string => $record->remainingBalance()->format()),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('deposit_date', 'desc')
            ->paginated([5, 10, 25]);
    }
}
