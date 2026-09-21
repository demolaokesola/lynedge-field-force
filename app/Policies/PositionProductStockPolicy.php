<?php

namespace App\Policies;

use App\Models\PositionProductStock;
use App\Models\User;
use App\Services\StockLedger;

/**
 * Balances are materialised from the ledger by {@see StockLedger} and
 * never edited by hand. Read is open (rows narrowed by the model's visibleTo scope).
 */
class PositionProductStockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PositionProductStock $positionProductStock): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PositionProductStock $positionProductStock): bool
    {
        return false;
    }

    public function delete(User $user, PositionProductStock $positionProductStock): bool
    {
        return false;
    }
}
