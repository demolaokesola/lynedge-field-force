<?php

namespace App\Policies;

use App\Models\Concerns\ScopesToViewer;
use App\Models\Deposit;
use App\Models\User;

/**
 * Write-side guard for deposits (the read side is {@see ScopesToViewer}).
 * Reps record deposits; accountants and admins own all subsequent management.
 * Superuser is granted everything via Shield's Gate::before.
 */
class DepositPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deposit $deposit): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['sales_rep', 'supervisor', 'accountant', 'platform_admin']);
    }

    public function update(User $user, Deposit $deposit): bool
    {
        return $user->hasAnyRole(['accountant', 'platform_admin']);
    }

    public function delete(User $user, Deposit $deposit): bool
    {
        return $user->hasAnyRole(['accountant', 'platform_admin']);
    }
}
