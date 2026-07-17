<?php

namespace App\Filament\Field\Widgets;

use App\Models\Cycle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NoCurrentCycleNoticeWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Cycle::where('is_current', true)->doesntExist();
    }

    protected function getStats(): array
    {
        return [
            Stat::make('No Active Cycle', 'No sales cycle is currently running — targets and attainment will show as "—" until one starts.')
                ->color('warning')
                ->descriptionIcon('heroicon-m-clock'),
        ];
    }
}
