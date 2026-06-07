<?php

namespace App\Policies;

use App\Models\DepositAllocation;
use App\Models\User;

/**
 * Only accountants and admins may allocate deposits against distributions.
 * Superuser is granted everything via Shield's Gate::before.
 */
class DepositAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DepositAllocation $allocation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['accountant', 'platform_admin']);
    }

    public function update(User $user, DepositAllocation $allocation): bool
    {
        return $user->hasAnyRole(['accountant', 'platform_admin']);
    }

    public function delete(User $user, DepositAllocation $allocation): bool
    {
        return $user->hasAnyRole(['accountant', 'platform_admin']);
    }
}
