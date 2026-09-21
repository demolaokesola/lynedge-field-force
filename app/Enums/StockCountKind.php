<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockCountKind: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    /** Sets a position's starting balances at go-live: uncounted catalogue products are taken as zero. */
    case Opening = 'opening';

    /** A routine physical count: only the products actually counted are reconciled. */
    case Periodic = 'periodic';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Opening => 'primary',
            self::Periodic => 'gray',
        };
    }
}
