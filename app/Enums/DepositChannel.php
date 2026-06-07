<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DepositChannel: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Cash => 'success',
            self::BankTransfer => 'info',
            self::Cheque => 'warning',
        };
    }
}
