<?php

namespace App\Filament\Field\Resources\StockAdjustments\Pages;

use App\Filament\Field\Resources\StockAdjustments\StockAdjustmentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStockAdjustment extends ViewRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
