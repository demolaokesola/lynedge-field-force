<?php

namespace App\Filament\Field\Resources\Products\Pages;

use App\Filament\Field\Resources\Products\ProductResource;
use App\Filament\Field\Widgets\ProductPerformanceOverviewWidget;
use App\Filament\Field\Widgets\ProductPerformanceTrendWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ProductPerformanceOverviewWidget::class,
            ProductPerformanceTrendWidget::class,
        ];
    }
}
