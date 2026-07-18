<?php

namespace App\Filament\Field\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupervisorScopeNoticeWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()?->isSupervisor() ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Viewing Supervised Data', 'The sections below show activity for the reps you supervise, not just you.')
                ->color('info')
                ->descriptionIcon('heroicon-m-information-circle'),
        ];
    }
}
