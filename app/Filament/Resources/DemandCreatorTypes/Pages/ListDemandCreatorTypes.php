<?php

namespace App\Filament\Resources\DemandCreatorTypes\Pages;

use App\Filament\Resources\DemandCreatorTypes\DemandCreatorTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDemandCreatorTypes extends ListRecords
{
    protected static string $resource = DemandCreatorTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
