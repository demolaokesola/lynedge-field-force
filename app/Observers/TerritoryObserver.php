<?php

namespace App\Observers;

use App\Enums\TeamPolicy;
use App\Models\Territory;

class TerritoryObserver
{
    /**
     * When a territory's policy flips (rare — a reorg), re-sync its positions'
     * enforce_team_uniqueness so the strict partial index tracks the new policy.
     * When its code changes, re-derive its positions' codes (each position must be
     * re-saved individually, not bulk-updated, so PositionObserver's
     * collision-avoiding derivation re-runs for every affected row).
     */
    public function updated(Territory $territory): void
    {
        if ($territory->wasChanged('team_policy')) {
            $territory->positions()->update([
                'enforce_team_uniqueness' => $territory->team_policy === TeamPolicy::Strict,
            ]);
        }

        if ($territory->wasChanged('code')) {
            $territory->positions()->orderBy('id')->get()->each->save();
        }
    }
}
