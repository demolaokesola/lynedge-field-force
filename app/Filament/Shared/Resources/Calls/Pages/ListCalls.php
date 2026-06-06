<?php

namespace App\Filament\Shared\Resources\Calls\Pages;

use App\Filament\Shared\Resources\Calls\CallResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden automatically when CallPolicy::create() denies (e.g. management roles).
            CreateAction::make(),
        ];
    }
}
