<?php

namespace App\Enums;

use App\Services\RepScope;
use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle of a Position (positions.status).
 *
 * Only `active` positions participate in the strict (territory, team) uniqueness
 * guarantee and in {@see RepScope::invoiceablePositions()}. A `frozen`
 * position is parked (e.g. during a reorg) and excluded from both.
 */
enum PositionStatus: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Active = 'active';
    case Frozen = 'frozen';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Frozen => 'gray',
        };
    }
}
