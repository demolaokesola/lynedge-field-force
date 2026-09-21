<?php

namespace App\Filament\Management\Widgets;

use App\Models\PositionProductStock;
use App\Models\Territory;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Stock on hand per region and product, summed across the positions in scope for the
 * viewer (all regions for hq_lead, their own for regional_head). Negative totals mean
 * the region as a whole has sold more than it has recorded receiving.
 */
class RegionStockRollupWidget extends BaseWidget
{
    protected static ?string $heading = 'Stock on Hand: Region → Product';

    protected static ?int $sort = 6;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['superuser', 'hq_lead', 'regional_head']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildQuery(auth()->user()))
            ->columns([
                TextColumn::make('region_name')
                    ->label('Region')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position_count')
                    ->label('Positions')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('negative_count')
                    ->label('In deficit')
                    ->numeric()
                    ->color(fn (int $state): ?string => $state > 0 ? 'danger' : null)
                    ->sortable(),
                TextColumn::make('on_hand')
                    ->label('On hand')
                    ->numeric(2)
                    ->color(fn (string $state): ?string => (float) $state < 0 ? 'danger' : null)
                    ->sortable(),
            ])
            ->defaultSort('region_name')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    private function buildQuery(?User $viewer): Builder
    {
        // Aggregate in an inner DB::table query so the GROUP BY is self-contained, then
        // wrap it via fromSub and point the model at the alias so Filament's pagination
        // tiebreaker becomes ORDER BY rollup.id (the ROW_NUMBER()) — see
        // CompanyRollupWidget for the same pattern.
        $inner = DB::table('position_product_stocks as s')
            ->join('positions as p', 'p.id', '=', 's.position_id')
            ->join('territories as t', 't.id', '=', 'p.territory_id')
            ->join('regions as r', 'r.id', '=', 't.region_id')
            ->join('products as pr', 'pr.id', '=', 's.product_id')
            ->whereIn('t.id', Territory::visibleOrgTo($viewer)->select('id'))
            ->groupBy('r.id', 'r.name', 'pr.id', 'pr.name')
            ->selectRaw('
                ROW_NUMBER() OVER (ORDER BY r.name, pr.name) AS id,
                r.name  AS region_name,
                pr.name AS product_name,
                COUNT(DISTINCT s.position_id) AS position_count,
                COUNT(DISTINCT s.position_id) FILTER (WHERE s.quantity < 0) AS negative_count,
                SUM(s.quantity) AS on_hand
            ');

        $model = new PositionProductStock;
        $model->setTable('rollup');

        return $model->newQuery()
            ->fromSub($inner, 'rollup')
            ->select('*');
    }
}
