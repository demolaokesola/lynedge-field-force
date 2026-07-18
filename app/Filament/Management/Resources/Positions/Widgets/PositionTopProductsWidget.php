<?php

namespace App\Filament\Management\Resources\Positions\Widgets;

use App\Enums\DistributionStatus;
use App\Models\DistributionLine;
use App\Models\Position;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PositionTopProductsWidget extends BaseWidget
{
    public ?Position $record = null;

    protected static ?string $heading = 'Top Products by Distribution';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $distributionIds = $this->record?->distributions()
            ->where('status', DistributionStatus::Posted->value)
            ->pluck('id') ?? collect();

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
                    ->numeric(2)
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
