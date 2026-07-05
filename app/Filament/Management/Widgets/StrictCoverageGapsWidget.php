<?php

namespace App\Filament\Management\Widgets;

use App\Enums\PositionStatus;
use App\Enums\TeamPolicy;
use App\Filament\Exports\VacantPositionsExporter;
use App\Models\Position;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class StrictCoverageGapsWidget extends BaseWidget
{
    protected static ?string $heading = 'Strict Coverage Gaps';

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query($this->buildQuery($user))
            ->columns([
                TextColumn::make('territory.region.name')
                    ->label('Region')
                    ->sortable(),
                TextColumn::make('territory.name')
                    ->label('Territory')
                    ->sortable(),
                TextColumn::make('team.name')
                    ->label('Strict Team')
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Position')
                    ->searchable(),
            ])
            ->defaultSort('territory.name')
            ->striped()
            ->emptyStateHeading('No strict coverage gaps')
            ->emptyStateDescription('All strict territory positions are currently occupied.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->headerActions([
                ExportAction::make()
                    ->exporter(VacantPositionsExporter::class)
                    ->formats([ExportFormat::Csv])
                    ->modifyQueryUsing(fn (Builder $query) => $this->buildQuery($user)),
            ]);
    }

    protected function buildQuery(?object $user): Builder
    {
        return Position::visibleOrgTo($user)
            ->where('status', PositionStatus::Active)
            ->whereDoesntHave('openAssignment')
            ->whereHas('territory', fn (Builder $q) => $q->where('team_policy', TeamPolicy::Strict->value))
            ->with('territory.region', 'team');
    }
}
