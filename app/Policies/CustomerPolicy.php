<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Master-data is admin-only for management/finance roles. sales_rep/supervisor may
 * additionally create, view (scoped to their territory — see Customer's
 * ScopesToTerritory), and edit rows they created, via the field panel. The superuser
 * role is granted everything automatically via Shield's Gate::before.
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep', 'supervisor']);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep', 'supervisor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep', 'supervisor']);
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->hasRole('platform_admin')) {
            return true;
        }

        return $user->hasAnyRole(['sales_rep', 'supervisor']) && $customer->created_by === $user->id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
