<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DepositStatus: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Unreconciled = 'unreconciled';
    case PartiallyReconciled = 'partially_reconciled';
    case Reconciled = 'reconciled';
    case Disputed = 'disputed';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Unreconciled => 'warning',
            self::PartiallyReconciled => 'info',
            self::Reconciled => 'success',
            self::Disputed => 'danger',
        };
    }
}
