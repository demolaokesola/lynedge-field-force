<?php

namespace App\Policies;

use App\Models\DemandCreatorType;
use App\Models\User;

/**
 * Master-data is admin-only. Every ability is gated to platform_admin; the superuser
 * role is granted everything automatically via Shield's Gate::before.
 */
class DemandCreatorTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function view(User $user, DemandCreatorType $demandCreatorType): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, DemandCreatorType $demandCreatorType): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, DemandCreatorType $demandCreatorType): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
