<?php

namespace App\Filament\Field\Widgets;

use App\Models\Cycle;
use App\Services\AttainmentService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RepPerformanceOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return 'My Target Attainment (Cycle to Date)';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $cycle = Cycle::where('is_current', true)->first();

        if ($user === null || $cycle === null) {
            return [
                Stat::make('Target YTD (units)', '—'),
                Stat::make('Actual YTD (units)', '—'),
                Stat::make('Attainment %', '—'),
            ];
        }

        $service = app(AttainmentService::class);

        $targetYtd = $service->targetYtdForRep($user, $cycle, now()->startOfMonth());
        $actualYtd = $service->actualYtdForRep($user, $cycle, now());
        $attainmentPct = $service->attainmentPct($targetYtd, $actualYtd);

        return [
            Stat::make('Target YTD (units)', number_format($targetYtd, 2)),
            Stat::make('Actual YTD (units)', number_format($actualYtd, 2)),
            Stat::make('Attainment %', $attainmentPct === null ? '—' : number_format($attainmentPct, 1).'%')
                ->color(match (true) {
                    $attainmentPct === null => 'gray',
                    $attainmentPct >= 100 => 'success',
                    $attainmentPct >= 75 => 'warning',
                    default => 'danger',
                }),
        ];
    }
}
