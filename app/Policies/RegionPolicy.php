<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

/**
 * Org master-data is admin-only. Every ability is gated to platform_admin; the
 * superuser role is granted everything automatically via Shield's Gate::before.
 */
class RegionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function view(User $user, Region $region): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, Region $region): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, Region $region): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
