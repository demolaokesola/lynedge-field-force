<?php

namespace App\Policies;

use App\Models\DemandCreator;
use App\Models\User;

/**
 * Master-data is admin-only. Every ability is gated to platform_admin; the superuser
 * role is granted everything automatically via Shield's Gate::before.
 */
class DemandCreatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function view(User $user, DemandCreator $demandCreator): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, DemandCreator $demandCreator): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, DemandCreator $demandCreator): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
