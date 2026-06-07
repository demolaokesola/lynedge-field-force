<?php

namespace App\Filament\Widgets;

use App\Enums\DistributionStatus;
use App\Filament\Exports\LeaderboardExporter;
use App\Models\Cycle;
use App\Models\DistributionLine;
use App\Models\RepMonthlyTarget;
use App\Models\User;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopBottomRepsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top & Bottom Reps — Current Cycle';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['superuser', 'platform_admin']) ?? false;
    }

    public function table(Table $table): Table
    {
        $cycle = Cycle::where('is_current', true)->first();

        return $table
            ->query($this->buildQuery($cycle))
            ->columns([
                TextColumn::make('name')
                    ->label('Rep')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_ytd')
                    ->label('Target YTD')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('actual_ytd')
                    ->label('Actual YTD')
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
            ->defaultSort('attainment_pct', 'desc')
            ->striped()
            ->headerActions([
                ExportAction::make()
                    ->exporter(LeaderboardExporter::class)
                    ->formats([ExportFormat::Csv])
                    ->modifyQueryUsing(fn (Builder $query) => $this->buildQuery($cycle)),
            ]);
    }

    private function buildQuery(?Cycle $cycle): Builder
    {
        if ($cycle === null) {
            return User::query()->whereRaw('1 = 0');
        }

        $targets = RepMonthlyTarget::query()
            ->where('cycle_id', $cycle->id)
            ->where('year_month', '<=', now()->startOfMonth())
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(target_qty) AS target_ytd');

        $actuals = DistributionLine::query()
            ->join('distributions', 'distributions.id', 'distribution_lines.distribution_id')
            ->where('distributions.status', DistributionStatus::Posted->value)
            ->whereBetween('distributions.invoice_date', [$cycle->starts_on, now()])
            ->groupBy('distributions.user_id')
            ->selectRaw('distributions.user_id, SUM(distribution_lines.quantity) AS actual_ytd');

        return User::query()
            ->whereHas('positionAssignments')
            ->leftJoinSub($targets, 'tgt', 'tgt.user_id', 'users.id')
            ->leftJoinSub($actuals, 'act', 'act.user_id', 'users.id')
            ->whereNotNull('tgt.target_ytd')
            ->selectRaw('
                users.id,
                users.name,
                COALESCE(tgt.target_ytd, 0) AS target_ytd,
                COALESCE(act.actual_ytd, 0) AS actual_ytd,
                CASE WHEN COALESCE(tgt.target_ytd, 0) > 0
                     THEN ROUND(COALESCE(act.actual_ytd, 0) / tgt.target_ytd * 100, 1)
                     ELSE NULL
                END AS attainment_pct
            ');
    }
}
