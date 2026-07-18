<?php

namespace App\Filament\Field\Resources\StockDispatches\Pages;

use App\Filament\Field\Resources\StockDispatches\StockDispatchResource;
use Filament\Resources\Pages\ListRecords;

class ListStockDispatches extends ListRecords
{
    protected static string $resource = StockDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
