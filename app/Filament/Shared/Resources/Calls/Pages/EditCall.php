<?php

namespace App\Filament\Shared\Resources\Calls\Pages;

use App\Filament\Shared\Resources\Calls\CallResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCall extends EditRecord
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden automatically when CallPolicy::delete() denies.
            DeleteAction::make(),
        ];
    }
}
