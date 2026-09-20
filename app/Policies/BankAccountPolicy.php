<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;

/**
 * Finance master data: platform_admin and accountant maintain the company's bank
 * accounts. Superuser is granted everything via Shield's Gate::before. An account
 * that already has deposits cannot be deleted — deactivate it instead.
 */
class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isFinance($user);
    }

    public function view(User $user, BankAccount $bankAccount): bool
    {
        return $this->isFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->isFinance($user);
    }

    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $this->isFinance($user);
    }

    public function delete(User $user, BankAccount $bankAccount): bool
    {
        return $this->isFinance($user) && ! $bankAccount->deposits()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return $this->isFinance($user);
    }

    private function isFinance(User $user): bool
    {
        return $user->hasAnyRole(['platform_admin', 'accountant']);
    }
}
