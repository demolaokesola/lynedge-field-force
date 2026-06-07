<?php

namespace App\Filament\Shared\Resources\Deposits\Pages;

use App\Filament\Shared\Resources\Deposits\DepositResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeposit extends EditRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden automatically when DepositPolicy::delete() denies (field roles).
            DeleteAction::make(),
        ];
    }
}
