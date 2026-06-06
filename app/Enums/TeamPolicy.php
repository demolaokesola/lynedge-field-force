<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The selling policy of a territory (territories.team_policy).
 *
 * Strict territories partition the catalog across reps; liberal territories let
 * product groups overlap. A position's team.kind must equal its territory's policy
 * (enforced in Phase 2) — {@see TeamKind} carries the matching vocabulary on teams.
 */
enum TeamPolicy: string implements HasColor, HasLabel
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
