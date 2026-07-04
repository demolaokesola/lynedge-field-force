<?php

namespace App\Observers;

use App\Models\Position;
use App\Models\Team;

class TeamObserver
{
    /**
     * When a team's code changes (rare — a re-code), re-derive every position built
     * on it so `code` ({territory.code}-{team.code}) stays in sync. Positions are
     * re-saved individually so PositionObserver's collision-avoiding derivation
     * re-runs for every affected row.
     */
    public function updated(Team $team): void
    {
        if (! $team->wasChanged('code')) {
            return;
        }

        Position::query()->where('team_id', $team->id)->orderBy('id')->get()->each->save();
    }
}
