<?php

namespace App\Filament\Resources\TargetTiers\Pages;

use App\Filament\Resources\TargetTiers\TargetTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTargetTier extends EditRecord
{
    protected static string $resource = TargetTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
