<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasLabel;

enum AssignmentReason: string implements HasLabel
{
    use HasFilamentEnum;

    case Initial = 'initial';
    case TierChange = 'tier_change';
    case Maternity = 'maternity';
    case Leave = 'leave';
    case Adjustment = 'adjustment';
    case Custom = 'custom';
}
