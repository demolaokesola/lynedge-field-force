<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockMovementType: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case DispatchAcceptance = 'dispatch_acceptance';
    case Adjustment = 'adjustment';
    case Distribution = 'distribution';
    case StockCount = 'stock_count';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DispatchAcceptance => 'success',
            self::Adjustment => 'info',
            self::Distribution => 'warning',
            self::StockCount => 'primary',
        };
    }
}
