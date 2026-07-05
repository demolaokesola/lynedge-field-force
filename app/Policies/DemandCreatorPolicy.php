<?php

namespace App\Policies;

use App\Models\DemandCreator;
use App\Models\User;

/**
 * Master-data is admin-only for management/finance roles. sales_rep/supervisor may
 * additionally create, view (scoped to their territory — see DemandCreator's
 * ScopesToTerritory), and edit rows they created, via the field panel. The superuser
 * role is granted everything automatically via Shield's Gate::before.
 */
class DemandCreatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep', 'supervisor']);
    }

    public function view(User $user, DemandCreator $demandCreator): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep', 'supervisor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep', 'supervisor']);
    }

    public function update(User $user, DemandCreator $demandCreator): bool
    {
        if ($user->hasRole('platform_admin')) {
            return true;
        }

        return $user->hasAnyRole(['sales_rep', 'supervisor']) && $demandCreator->created_by === $user->id;
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
