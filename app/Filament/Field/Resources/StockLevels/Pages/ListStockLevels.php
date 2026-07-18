<?php

namespace App\Filament\Field\Resources\StockLevels\Pages;

use App\Filament\Field\Resources\StockLevels\StockLevelResource;
use Filament\Resources\Pages\ListRecords;

class ListStockLevels extends ListRecords
{
    protected static string $resource = StockLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
