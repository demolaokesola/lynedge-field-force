<?php

namespace App\Filament\Office\Resources\TargetAssignments\Pages;

use App\Filament\Office\Resources\TargetAssignments\TargetAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTargetAssignment extends EditRecord
{
    protected static string $resource = TargetAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
