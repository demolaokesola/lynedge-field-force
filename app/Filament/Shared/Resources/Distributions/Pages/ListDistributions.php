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
            CreateAction::make(),
        ];
    }
}
