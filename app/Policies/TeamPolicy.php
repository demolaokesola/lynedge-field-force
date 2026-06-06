<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Org master-data is admin-only. Every ability is gated to platform_admin; the
 * superuser role is granted everything automatically via Shield's Gate::before.
 *
 * Note: this is the authorization policy for the Team model. The strict|liberal
 * vocabulary on teams lives in the unrelated App\Enums\TeamKind enum.
 */
class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function view(User $user, Team $team): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
