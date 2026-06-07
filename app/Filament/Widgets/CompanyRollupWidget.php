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
        // ROW_NUMBER() window function is evaluated after GROUP BY in PostgreSQL,
        // giving each grouped row a stable unique id for Filament's table.
        return Distribution::query()
            ->join('territories', 'territories.id', 'distributions.territory_id')
            ->join('regions', 'regions.id', 'territories.region_id')
            ->join('teams', 'teams.id', 'distributions.team_id')
            ->where('distributions.status', DistributionStatus::Posted->value)
            ->groupBy('regions.id', 'regions.name', 'territories.id', 'territories.name', 'teams.id', 'teams.name')
            ->selectRaw('
                ROW_NUMBER() OVER (ORDER BY regions.name, territories.name, teams.name) AS id,
                regions.name   AS region_name,
                territories.name AS territory_name,
                teams.name     AS team_name,
                COUNT(DISTINCT distributions.id) AS invoice_count,
                SUM(distributions.total_amount)  AS total_value
            ')
            ->orderBy('regions.name')
            ->orderBy('territories.name')
            ->orderBy('teams.name');
    }
}
