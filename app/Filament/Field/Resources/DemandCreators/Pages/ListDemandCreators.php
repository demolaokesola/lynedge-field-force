<?php

namespace App\Filament\Field\Resources\DemandCreators\Pages;

use App\Filament\Field\Resources\DemandCreators\DemandCreatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDemandCreators extends ListRecords
{
    protected static string $resource = DemandCreatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
