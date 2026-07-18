<?php

namespace App\Filament\Office\Resources\StockDispatches\Pages;

use App\Filament\Office\Resources\StockDispatches\StockDispatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockDispatches extends ListRecords
{
    protected static string $resource = StockDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
