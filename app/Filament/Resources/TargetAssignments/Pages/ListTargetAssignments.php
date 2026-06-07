<?php

namespace App\Filament\Resources\TargetAssignments\Pages;

use App\Filament\Resources\TargetAssignments\TargetAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTargetAssignments extends ListRecords
{
    protected static string $resource = TargetAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
