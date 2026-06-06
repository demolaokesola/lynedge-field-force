<?php

namespace App\Policies;

use App\Models\Call;
use App\Models\Concerns\ScopesToViewer;
use App\Models\User;

/**
 * Write-side guard for calls (the read side is {@see ScopesToViewer}).
 * Only position-holding field roles may write, and only their OWN rows — this is what
 * makes the management panel (hq_lead, regional_head) read-only. The superuser role is
 * granted everything automatically via Shield's Gate::before.
 *
 * Read abilities return true and lean on the resource's getEloquentQuery() scope to
 * narrow which rows a viewer can actually reach.
 */
class CallPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Call $call): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['sales_rep', 'supervisor']);
    }

    public function update(User $user, Call $call): bool
    {
        return $this->ownsCall($user, $call);
    }

    public function delete(User $user, Call $call): bool
    {
        return $this->ownsCall($user, $call);
    }

    private function ownsCall(User $user, Call $call): bool
    {
        return $user->hasAnyRole(['sales_rep', 'supervisor'])
            && $call->user_id === $user->id;
    }
}
