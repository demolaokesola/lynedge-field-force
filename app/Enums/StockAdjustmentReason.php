<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockAdjustmentReason: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Damage = 'damage';
    case Loss = 'loss';
    case Correction = 'correction';
    case Return = 'return';
    case Recall = 'recall';
    case Other = 'other';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Damage, self::Loss, self::Recall => 'danger',
            self::Correction, self::Return => 'info',
            self::Other => 'gray',
        };
    }
}
