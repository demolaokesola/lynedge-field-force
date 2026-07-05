<?php

namespace App\Filament\Office\Resources\TargetTiers\Pages;

use App\Filament\Office\Resources\TargetTiers\TargetTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTargetTiers extends ListRecords
{
    protected static string $resource = TargetTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
