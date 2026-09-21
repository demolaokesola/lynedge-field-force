<?php

namespace App\Filament\Field\Resources\StockCounts\Pages;

use App\Filament\Field\Resources\StockCounts\StockCountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockCounts extends ListRecords
{
    protected static string $resource = StockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
