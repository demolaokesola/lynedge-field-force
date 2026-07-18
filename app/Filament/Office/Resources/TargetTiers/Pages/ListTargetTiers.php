<?php

namespace App\Filament\Office\Resources\TargetTiers\Pages;

use App\Filament\Office\Resources\TargetTiers\TargetTierResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTargetTiers extends ListRecords
{
    protected static string $resource = TargetTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editVolumes')
                ->label('Edit Annual Volumes')
                ->icon(Heroicon::OutlinedTableCells)
                ->url(TargetTierResource::getUrl('volumes')),
            CreateAction::make(),
        ];
    }
}
