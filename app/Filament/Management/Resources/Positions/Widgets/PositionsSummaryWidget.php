<?php

namespace App\Filament\Management\Resources\Positions\Widgets;

use App\Enums\PositionStatus;
use App\Models\Position;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PositionsSummaryWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();

        $total = Position::visibleOrgTo($user)->count();
        $active = Position::visibleOrgTo($user)->where('status', PositionStatus::Active)->count();
        $vacant = Position::visibleOrgTo($user)
            ->where('status', PositionStatus::Active)
            ->whereDoesntHave('openAssignment')
            ->count();
        $occupiedPct = $active > 0 ? round((($active - $vacant) / $active) * 100, 1) : null;

        return [
            Stat::make('Total Positions', $total),
            Stat::make('Active', $active),
            Stat::make('Vacant', $vacant)
                ->color($vacant > 0 ? 'danger' : 'success'),
            Stat::make('Occupied %', $occupiedPct === null ? '—' : number_format($occupiedPct, 1).'%'),
        ];
    }
}
