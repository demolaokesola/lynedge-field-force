<?php

namespace App\Filament\Field\Resources\Customers\Widgets;

use App\Models\Customer;
use App\Models\Distribution;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CustomerDistributionsWidget extends BaseWidget
{
    public ?Customer $record = null;

    protected static ?string $heading = 'Distributions';

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                Distribution::visibleTo($user)
                    ->where('customer_id', $this->record?->id)
                    ->latest('invoice_date')
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable(),
                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total (₦)')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->paginated([5, 10, 25]);
    }
}
