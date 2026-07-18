<?php

namespace App\Policies;

use App\Enums\StockAdjustmentStatus;
use App\Models\Concerns\ScopesToPosition;
use App\Models\StockAdjustment;
use App\Models\User;

/**
 * Write-side guard for stock adjustments (the read side is
 * {@see ScopesToPosition}). Operations (platform_admin) owns the
 * whole lifecycle — reps have read-only visibility. Superuser is granted everything via
 * Shield's Gate::before.
 *
 * Posting is the submit stage: once an adjustment leaves Draft, it is frozen — update,
 * delete, and post itself all require status===Draft, mirroring DistributionPolicy.
 */
class StockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockAdjustment $stockAdjustment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $this->isDraftByOperations($user, $stockAdjustment);
    }

    public function delete(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $this->isDraftByOperations($user, $stockAdjustment);
    }

    public function post(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $this->isDraftByOperations($user, $stockAdjustment);
    }

    public function void(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $this->isDraftByOperations($user, $stockAdjustment);
    }

    private function isDraftByOperations(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->hasRole('platform_admin') && $stockAdjustment->status === StockAdjustmentStatus::Draft;
    }
}
