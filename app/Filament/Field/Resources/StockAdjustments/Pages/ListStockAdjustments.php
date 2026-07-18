<?php

namespace App\Filament\Field\Resources\StockAdjustments\Pages;

use App\Filament\Field\Resources\StockAdjustments\StockAdjustmentResource;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
