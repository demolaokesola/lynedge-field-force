<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

/**
 * Positions are org master-data. Write abilities are admin-only (platform_admin);
 * the superuser role is granted everything via Shield's Gate::before. Read abilities
 * are open — row-level access for the management panel's read-only resource is fully
 * delegated to {@see Position::scopeVisibleOrgTo()}, applied in that resource's
 * getEloquentQuery(), matching the CallPolicy/DistributionPolicy convention.
 */
class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Position $position): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, Position $position): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
