<?php

namespace App\Filament\Shared\Resources\Deposits\Pages;

use App\Filament\Shared\Resources\Deposits\DepositResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden automatically when DepositPolicy::create() denies (management/hq roles).
            CreateAction::make(),
        ];
    }
}
