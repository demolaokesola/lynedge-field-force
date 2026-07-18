<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockDispatchStatus: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Draft = 'draft';
    case Dispatched = 'dispatched';
    case Accepted = 'accepted';
    case Void = 'void';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Dispatched => 'warning',
            self::Accepted => 'success',
            self::Void => 'danger',
        };
    }
}
