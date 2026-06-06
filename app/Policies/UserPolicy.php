<?php

namespace App\Policies;

use App\Models\User;

/**
 * User & role administration is admin-only. Every ability is gated to platform_admin;
 * the superuser role is granted everything automatically via Shield's Gate::before.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
