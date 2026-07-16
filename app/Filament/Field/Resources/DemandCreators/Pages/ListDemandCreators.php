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
            // Navigate to the dedicated create page instead of opening the default
            // modal — position_id -> territory_id derivation only happens there
            // (see CreateDemandCreator::mutateFormDataBeforeCreate).
            CreateAction::make()
                ->url(fn (): string => DemandCreatorResource::getUrl('create')),
        ];
    }
}
