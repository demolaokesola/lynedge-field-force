<?php

namespace App\Filament\Management\Widgets;

use App\Filament\Exports\CallCoverageExporter;
use App\Models\Call;
use App\Models\Territory;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CallCoverageWidget extends BaseWidget
{
    protected static ?string $heading = 'Call Coverage by Territory — This Month';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query($this->buildQuery($user))
            ->columns([
                TextColumn::make('region_name')
                    ->label('Region')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Territory')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('call_count')
                    ->label('Calls This Month')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'success'),
            ])
            ->defaultSort('call_count', 'desc')
            ->striped()
            ->headerActions([
                ExportAction::make()
                    ->exporter(CallCoverageExporter::class)
                    ->formats([ExportFormat::Csv])
                    ->modifyQueryUsing(fn (Builder $query) => $this->buildQuery($user)),
            ]);
    }

    private function buildQuery(?object $user): Builder
    {
        $monthCalls = Call::query()
            ->whereYear('called_at', now()->year)
            ->whereMonth('called_at', now()->month)
            ->groupBy('territory_id')
            ->selectRaw('territory_id, COUNT(*) AS call_count');

        return Territory::visibleOrgTo($user)
            ->join('regions', 'regions.id', 'territories.region_id')
            ->leftJoinSub($monthCalls, 'mc', 'mc.territory_id', 'territories.id')
            ->selectRaw('
                territories.id,
                territories.name,
                regions.name AS region_name,
                COALESCE(mc.call_count, 0) AS call_count
            ')
            ->orderByRaw('COALESCE(mc.call_count, 0) DESC');
    }
}
