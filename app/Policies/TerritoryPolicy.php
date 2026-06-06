<?php

namespace App\Policies;

use App\Models\Territory;
use App\Models\User;

/**
 * Org master-data is admin-only. Every ability is gated to platform_admin; the
 * superuser role is granted everything automatically via Shield's Gate::before.
 */
class TerritoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function view(User $user, Territory $territory): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, Territory $territory): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, Territory $territory): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
