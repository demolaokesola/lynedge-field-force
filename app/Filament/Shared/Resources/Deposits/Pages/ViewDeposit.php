<?php

namespace App\Filament\Shared\Resources\Deposits\Pages;

use App\Filament\Shared\Resources\Deposits\Actions\DisputeActions;
use App\Filament\Shared\Resources\Deposits\Actions\ReconciliationActions;
use App\Filament\Shared\Resources\Deposits\DepositResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only deposit details. Reps land here from the deposits list; every header
 * action is gated by DepositPolicy::update, so they see none of them.
 */
class ViewDeposit extends ViewRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ...ReconciliationActions::make(),
            ...DisputeActions::make(),
        ];
    }
}
