<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * State of a position assignment (position_assignments.status).
 *
 * An assignment is `active` while it is the open occupancy of its position
 * (effective_to IS NULL); it becomes `ended` once effective_to is set.
 */
enum AssignmentStatus: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Active = 'active';
    case Ended = 'ended';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Ended => 'gray',
        };
    }
}
