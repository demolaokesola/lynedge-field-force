<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DepositStatus: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Unreconciled = 'unreconciled';
    case Reconciled = 'reconciled';
    case Disputed = 'disputed';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Unreconciled => 'warning',
            self::Reconciled => 'success',
            self::Disputed => 'danger',
        };
    }
}
