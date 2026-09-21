<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockLedger;

/**
 * The ledger is append-only and written solely by {@see StockLedger}.
 * Read is open (rows narrowed by ScopesToPosition); nothing else is ever allowed, not
 * even for platform_admin.
 */
class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StockMovement $stockMovement): bool
    {
        return false;
    }

    public function delete(User $user, StockMovement $stockMovement): bool
    {
        return false;
    }
}
