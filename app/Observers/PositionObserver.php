<?php

namespace App\Observers;

use App\Enums\TeamPolicy;
use App\Models\Position;

class PositionObserver
{
    /**
     * Keep enforce_team_uniqueness in lockstep with the territory's policy, so the
     * partial unique index (positions_strict_team_unique) only ever bites strict
     * territories. This is server-managed; the flag is never client-set.
     */
    public function saving(Position $position): void
    {
        $position->enforce_team_uniqueness =
            $position->territory->team_policy === TeamPolicy::Strict;
    }
}
