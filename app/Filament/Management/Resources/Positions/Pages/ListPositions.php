<?php

namespace App\Filament\Management\Resources\Positions\Pages;

use App\Filament\Management\Resources\Positions\PositionResource;
use App\Filament\Management\Resources\Positions\Widgets\PositionsSummaryWidget;
use Filament\Resources\Pages\ListRecords;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PositionsSummaryWidget::class,
        ];
    }
}
