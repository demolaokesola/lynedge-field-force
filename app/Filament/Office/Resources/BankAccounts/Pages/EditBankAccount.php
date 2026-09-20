<?php

namespace App\Filament\Office\Resources\BankAccounts\Pages;

use App\Filament\Office\Resources\BankAccounts\BankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden automatically when BankAccountPolicy::delete() denies (account has deposits).
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
