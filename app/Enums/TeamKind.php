<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * How a team sells (teams.kind).
 *
 * A product may belong to at most one strict team and any number of liberal teams
 * (enforced on pivot-attach in Phase 3). A team may only man a territory whose
 * team_policy matches this kind ({@see TeamPolicy}).
 */
enum TeamKind: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Strict = 'strict';
    case Liberal = 'liberal';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Strict => 'danger',
            self::Liberal => 'info',
        };
    }
}
