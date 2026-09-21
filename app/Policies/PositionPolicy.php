<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

/**
 * Positions are org master-data. Write abilities are admin-only (platform_admin);
 * the superuser role is granted everything via Shield's Gate::before.
 *
 * Unlike Region/Territory/Team, positions also have a read-only Management resource,
 * so read abilities extend to hq_lead and regional_head. Row-level access for that
 * resource is delegated to {@see Position::scopeVisibleOrgTo()}, applied in its
 * getEloquentQuery(). The accountant is deliberately excluded so Positions stay out
 * of their Office navigation.
 */
class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, Position $position): bool
    {
        return $this->canRead($user);
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

    private function canRead(User $user): bool
    {
        return $user->hasAnyRole(['platform_admin', 'hq_lead', 'regional_head']);
    }
}
