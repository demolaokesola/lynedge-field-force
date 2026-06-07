<?php

namespace App\Filament\Shared\Resources\Deposits\Pages;

use App\Filament\Shared\Resources\Deposits\DepositResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    /**
     * Set the protected columns that are not mass-assignable.
     * territory_id is derived in the Deposit::creating() boot event from the customer.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel());
        $record->fill($data);
        // Honour an explicit override (accountant recording on behalf of a rep),
        // otherwise default to the currently authenticated user.
        $record->user_id = $data['user_id'] ?? auth()->id();
        $record->save();

        return $record;
    }
}
