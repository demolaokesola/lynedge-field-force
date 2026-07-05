<?php

namespace App\Filament\Management\Widgets;

use App\Enums\PositionStatus;
use App\Filament\Exports\VacantPositionsExporter;
use App\Models\Position;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class VacantPositionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Vacant Positions';

    protected static ?int $sort = 3;

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
                    ->label('Team')
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Position')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('territory.name')
            ->striped()
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
            ->with('territory.region', 'team');
    }
}
