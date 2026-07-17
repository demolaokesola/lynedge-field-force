<?php

namespace App\Filament\Shared\Resources\Distributions\Pages;

use App\Filament\Shared\Resources\Distributions\DistributionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDistributions extends ListRecords
{
    protected static string $resource = DistributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden automatically when DistributionPolicy::create() denies (management roles).
            // Navigate to the dedicated create page instead of opening the default modal —
            // user_id/territory_id/team_id derivation and the product guard only happen there
            // (see CreateDistribution::handleRecordCreation/mutateFormDataBeforeCreate).
            CreateAction::make()
                ->url(fn (): string => DistributionResource::getUrl('create')),
        ];
    }
}
