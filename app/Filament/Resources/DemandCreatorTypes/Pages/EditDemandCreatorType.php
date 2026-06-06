<?php

namespace App\Filament\Resources\DemandCreatorTypes\Pages;

use App\Filament\Resources\DemandCreatorTypes\DemandCreatorTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDemandCreatorType extends EditRecord
{
    protected static string $resource = DemandCreatorTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
