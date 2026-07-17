<?php

namespace App\Policies;

use App\Enums\DistributionStatus;
use App\Models\Concerns\ScopesToViewer;
use App\Models\Distribution;
use App\Models\User;

/**
 * Write-side guard for distributions (the read side is {@see ScopesToViewer}).
 * Only position-holding field roles may write, and only their OWN rows — this makes
 * the management panel read-only. Superuser is granted everything via Shield's Gate::before.
 *
 * Read abilities return true and lean on getEloquentQuery() scope to narrow rows.
 *
 * Posting is the submit stage: once a distribution leaves Draft, it is frozen — update,
 * delete, and post itself all require status===Draft. This is the only place that's enforced.
 */
class DistributionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Distribution $distribution): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['sales_rep', 'supervisor']);
    }

    public function update(User $user, Distribution $distribution): bool
    {
        return $this->ownsDraftDistribution($user, $distribution);
    }

    public function delete(User $user, Distribution $distribution): bool
    {
        return $this->ownsDraftDistribution($user, $distribution);
    }

    public function post(User $user, Distribution $distribution): bool
    {
        return $this->ownsDraftDistribution($user, $distribution);
    }

    private function ownsDraftDistribution(User $user, Distribution $distribution): bool
    {
        return $user->hasAnyRole(['sales_rep', 'supervisor'])
            && $distribution->user_id === $user->id
            && $distribution->status === DistributionStatus::Draft;
    }
}
