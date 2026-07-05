<?php

namespace App\Filament\Office\Resources\TargetAssignments\Pages;

use App\Filament\Office\Resources\TargetAssignments\TargetAssignmentResource;
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
