<?php

namespace App\Filament\Field\Widgets;

use App\Services\RepScope;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NoActivePositionNoticeWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && app(RepScope::class)->activePositions($user)->isEmpty();
    }

    /**
     * A single notice card should fill the row, not sit in one third of the stats grid.
     */
    protected function getColumns(): int
    {
        return 1;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Setup Needed', "You're not assigned to an active position yet — contact your admin.")
                ->color('danger')
                ->descriptionIcon('heroicon-m-exclamation-triangle'),
        ];
    }
}
