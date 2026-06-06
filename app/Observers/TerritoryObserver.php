<?php

namespace App\Observers;

use App\Enums\TeamPolicy;
use App\Models\Territory;

class TerritoryObserver
{
    /**
     * When a territory's policy flips (rare — a reorg), re-sync its positions'
     * enforce_team_uniqueness so the strict partial index tracks the new policy.
     */
    public function updated(Territory $territory): void
    {
        if (! $territory->wasChanged('team_policy')) {
            return;
        }

        $territory->positions()->update([
            'enforce_team_uniqueness' => $territory->team_policy === TeamPolicy::Strict,
        ]);
    }
}
