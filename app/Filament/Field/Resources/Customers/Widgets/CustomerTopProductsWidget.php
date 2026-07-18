<?php

namespace App\Filament\Field\Resources\Customers\Widgets;

use App\Enums\DistributionStatus;
use App\Models\Customer;
use App\Models\Distribution;
use App\Models\DistributionLine;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CustomerTopProductsWidget extends BaseWidget
{
    public ?Customer $record = null;

    protected static ?string $heading = 'Top Products';

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $distributionIds = Distribution::visibleTo($user)
            ->where('customer_id', $this->record?->id)
            ->where('status', DistributionStatus::Posted->value)
            ->pluck('id');

        return $table
            ->query(
                DistributionLine::query()
                    ->join('products', 'products.id', 'distribution_lines.product_id')
                    ->whereIn('distribution_lines.distribution_id', $distributionIds)
                    ->groupBy('distribution_lines.product_id', 'products.name')
                    ->select('distribution_lines.product_id as id', 'products.name as product_name')
                    ->selectRaw('SUM(distribution_lines.quantity) as total_quantity')
                    ->selectRaw('SUM(distribution_lines.line_amount) as total_value')
            )
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product'),
                TextColumn::make('total_quantity')
                    ->label('Quantity')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('total_value')
                    ->label('Value (₦)')
                    ->money('NGN')
                    ->sortable(),
            ])
            ->defaultSort('total_value', 'desc')
            ->defaultKeySort(false)
            ->paginated([5, 10, 25]);
    }
}
