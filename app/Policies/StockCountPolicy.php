<?php

namespace App\Policies;

use App\Enums\StockCountKind;
use App\Enums\StockCountStatus;
use App\Models\Concerns\ScopesToPosition;
use App\Models\StockCount;
use App\Models\User;
use App\Services\RepScope;

/**
 * Write-side guard for stock counts (the read side is {@see ScopesToPosition}).
 * Operations (platform_admin) may create a count of either kind for any position and
 * owns posting and voiding. A rep may create a Periodic count for a position they
 * currently hold, and edits/submits their own draft. Superuser is granted everything
 * via Shield's Gate::before.
 *
 * Read abilities return true and lean on getEloquentQuery() scope to narrow rows.
 */
class StockCountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockCount $stockCount): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep']);
    }

    public function update(User $user, StockCount $stockCount): bool
    {
        return $this->isDraftOwnedBy($user, $stockCount);
    }

    public function delete(User $user, StockCount $stockCount): bool
    {
        return $this->isDraftOwnedBy($user, $stockCount);
    }

    public function submit(User $user, StockCount $stockCount): bool
    {
        return $this->isDraftOwnedBy($user, $stockCount);
    }

    /**
     * Operations posts Submitted counts, and Opening counts straight from Draft.
     */
    public function post(User $user, StockCount $stockCount): bool
    {
        if (! $user->hasRole('platform_admin')) {
            return false;
        }

        return $stockCount->status === StockCountStatus::Submitted
            || ($stockCount->status === StockCountStatus::Draft && $stockCount->kind === StockCountKind::Opening);
    }

    public function void(User $user, StockCount $stockCount): bool
    {
        return $user->hasRole('platform_admin')
            && in_array($stockCount->status, [StockCountStatus::Draft, StockCountStatus::Submitted], true);
    }

    /**
     * Operations may edit any draft; a rep only their own, and only while they still
     * hold the position.
     */
    private function isDraftOwnedBy(User $user, StockCount $stockCount): bool
    {
        if ($stockCount->status !== StockCountStatus::Draft) {
            return false;
        }

        if ($user->hasRole('platform_admin')) {
            return true;
        }

        return $user->hasRole('sales_rep')
            && $stockCount->counted_by_user_id === $user->id
            && app(RepScope::class)->activePositions($user)->contains('id', $stockCount->position_id);
    }
}
