<?php

namespace App\Filament\Field\Widgets;

use App\Enums\DistributionStatus;
use App\Models\Cycle;
use App\Models\DistributionLine;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class YtdAttainmentWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $cycle = Cycle::where('is_current', true)->first();
        $asOf = Carbon::now()->startOfMonth();

        return $table
            ->heading('My Target Attainment — By Product')
            ->query($this->buildQuery($user, $cycle, $asOf))
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('target_ytd')
                    ->label('Target YTD (units)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('actual_ytd')
                    ->label('Actual YTD (units)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('attainment_pct')
                    ->label('Attainment %')
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '—' : number_format($state, 1).'%')
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 100 => 'success',
                        $state >= 75 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
            ])
            ->defaultSort('product_name', 'asc')
            ->paginated(false)
            ->striped();
    }

    private function buildQuery(?object $user, ?Cycle $cycle, Carbon $asOf): Builder
    {
        if ($user === null || $cycle === null) {
            return Product::query()
                ->whereRaw('1 = 0')
                ->selectRaw('products.id, products.name AS product_name, NULL::numeric AS target_ytd, NULL::numeric AS actual_ytd, NULL::numeric AS attainment_pct');
        }

        $actuals = DistributionLine::query()
            ->join('distributions', 'distributions.id', 'distribution_lines.distribution_id')
            ->where('distributions.user_id', $user->id)
            ->where('distributions.status', DistributionStatus::Posted->value)
            ->whereBetween('distributions.invoice_date', [$cycle->starts_on, now()])
            ->groupBy('distribution_lines.product_id')
            ->selectRaw('distribution_lines.product_id, SUM(distribution_lines.quantity) as actual_ytd');

        // Use Product as the base model so products.id is in the GROUP BY.
        // Filament's default ORDER BY uses the model's primary key; with RepMonthlyTarget
        // as base, it tried ORDER BY rep_monthly_targets.id which is not in GROUP BY.
        return Product::query()
            ->join('rep_monthly_targets', 'rep_monthly_targets.product_id', '=', 'products.id')
            ->leftJoinSub($actuals, 'act', 'act.product_id', 'products.id')
            ->where('rep_monthly_targets.cycle_id', $cycle->id)
            ->where('rep_monthly_targets.user_id', $user->id)
            ->where('rep_monthly_targets.year_month', '<=', $asOf)
            ->groupBy('products.id', 'products.name')
            ->selectRaw('
                products.id,
                products.name AS product_name,
                SUM(rep_monthly_targets.target_qty) AS target_ytd,
                COALESCE(MAX(act.actual_ytd), 0) AS actual_ytd,
                CASE WHEN SUM(rep_monthly_targets.target_qty) > 0
                     THEN ROUND(COALESCE(MAX(act.actual_ytd), 0) / SUM(rep_monthly_targets.target_qty) * 100, 1)
                     ELSE NULL
                END AS attainment_pct
            ');
    }
}
