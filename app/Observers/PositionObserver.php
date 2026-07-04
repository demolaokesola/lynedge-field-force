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

        $position->code = self::deriveCode($position);
    }

    /**
     * {territory.code}-{team.code}, with a numeric suffix (-2, -3, ...) appended if
     * the base value collides with another position's code. Liberal territories/
     * teams allow multiple active positions on the same (territory, team) pair, so
     * this keeps the DB's global unique(code) constraint satisfied.
     */
    private static function deriveCode(Position $position): string
    {
        $base = "{$position->territory->code}-{$position->team->code}";

        $candidate = $base;
        $suffix = 2;

        while (
            Position::query()
                ->where('code', $candidate)
                ->when($position->exists, fn ($q) => $q->whereKeyNot($position->getKey()))
                ->exists()
        ) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
