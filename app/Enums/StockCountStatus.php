<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockCountStatus: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Posted = 'posted';
    case Void = 'void';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'warning',
            self::Posted => 'success',
            self::Void => 'danger',
        };
    }
}
