<?php

namespace App\Filament\Management\Resources\Positions\Pages;

use App\Filament\Management\Resources\Positions\PositionResource;
use App\Filament\Management\Resources\Positions\Widgets\PositionPerformanceChartWidget;
use App\Filament\Management\Resources\Positions\Widgets\PositionPerformanceOverviewWidget;
use App\Filament\Management\Resources\Positions\Widgets\PositionTopCustomersWidget;
use App\Filament\Management\Resources\Positions\Widgets\PositionTopProductsWidget;
use Filament\Resources\Pages\ViewRecord;

/**
 * The "Performance" tab — the default sub-navigation page for a position.
 */
class ViewPosition extends ViewRecord
{
    protected static string $resource = PositionResource::class;

    public static function getNavigationLabel(): string
    {
        return 'Performance';
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            PositionPerformanceOverviewWidget::class,
            PositionPerformanceChartWidget::class,
            PositionTopProductsWidget::class,
            PositionTopCustomersWidget::class,
        ];
    }
}
