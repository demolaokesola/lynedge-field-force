<?php

namespace App\Filament\Field\Widgets;

use App\Models\Distribution;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentDistributionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Distributions';

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(Distribution::visibleTo($user)->latest('invoice_date'))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Customer'),
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
