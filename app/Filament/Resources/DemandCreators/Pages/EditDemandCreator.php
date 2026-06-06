<?php

namespace App\Filament\Resources\DemandCreators\Pages;

use App\Filament\Resources\DemandCreators\DemandCreatorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDemandCreator extends EditRecord
{
    protected static string $resource = DemandCreatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
