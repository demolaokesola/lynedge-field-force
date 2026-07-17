<?php

namespace App\Filament\Field\Widgets;

use App\Services\RepScope;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NoActivePositionNoticeWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && app(RepScope::class)->activePositions($user)->isEmpty();
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
