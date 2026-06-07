<?php

namespace App\Filament\Widgets;

use App\Enums\DistributionStatus;
use App\Filament\Exports\CompanyRollupExporter;
use App\Models\Distribution;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CompanyRollupWidget extends BaseWidget
{
    protected static ?string $heading = 'Distribution Roll-up: Region → Territory → Team';

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['superuser', 'platform_admin']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildQuery())
            ->columns([
                TextColumn::make('region_name')
                    ->label('Region')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('territory_name')
                    ->label('Territory')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('team_name')
                    ->label('Team')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_count')
                    ->label('Invoices')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_value')
                    ->label('Total Value (₦)')
                    ->money('NGN')
                    ->sortable(),
            ])
            ->defaultSort('region_name')
            ->striped()
            ->headerActions([
                ExportAction::make()
                    ->exporter(CompanyRollupExporter::class)
                    ->formats([ExportFormat::Csv])
                    ->modifyQueryUsing(fn (Builder $query) => $this->buildQuery()),
            ]);
    }

    private function buildQuery(): Builder
    {
        // Aggregate in an inner DB::table query so the GROUP BY is self-contained.
        // Wrap it via fromSub with the alias 'rollup' and set the model's table to
        // 'rollup' so Filament's tiebreaker becomes ORDER BY rollup.id — the
        // ROW_NUMBER() result — instead of distributions.id which is not in GROUP BY.
        $inner = DB::table('distributions as d')
            ->join('territories as t', 't.id', '=', 'd.territory_id')
            ->join('regions as r', 'r.id', '=', 't.region_id')
            ->join('teams as tm', 'tm.id', '=', 'd.team_id')
            ->where('d.status', DistributionStatus::Posted->value)
            ->groupBy('r.id', 'r.name', 't.id', 't.name', 'tm.id', 'tm.name')
            ->selectRaw('
                ROW_NUMBER() OVER (ORDER BY r.name, t.name, tm.name) AS id,
                r.name   AS region_name,
                t.name   AS territory_name,
                tm.name  AS team_name,
                COUNT(DISTINCT d.id)  AS invoice_count,
                SUM(d.total_amount)   AS total_value
            ');

        $model = new Distribution;
        $model->setTable('rollup');

        return $model->newQuery()
            ->fromSub($inner, 'rollup')
            ->select('*');
    }
}
