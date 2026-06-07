<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TargetBasis: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Tier = 'tier';
    case Custom = 'custom';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Tier => 'info',
            self::Custom => 'warning',
        };
    }
}
