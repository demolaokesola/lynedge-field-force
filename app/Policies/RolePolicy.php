<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Role management is admin-only. Every ability is gated to platform_admin; the
 * superuser role is granted everything automatically via Shield's Gate::before.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
