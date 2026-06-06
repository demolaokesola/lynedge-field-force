<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

/**
 * Positions are org master-data — admin-only. Every ability is gated to
 * platform_admin; the superuser role is granted everything via Shield's Gate::before.
 */
class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function view(User $user, Position $position): bool
    {
        return $user->hasRole('platform_admin');
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
