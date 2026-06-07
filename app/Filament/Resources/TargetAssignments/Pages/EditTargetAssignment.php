<?php

namespace App\Filament\Resources\TargetAssignments\Pages;

use App\Filament\Resources\TargetAssignments\TargetAssignmentResource;
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
