<?php

namespace App\Policies;

use App\Enums\StockDispatchStatus;
use App\Models\Concerns\ScopesToPosition;
use App\Models\StockDispatch;
use App\Models\User;
use App\Services\RepScope;

/**
 * Write-side guard for stock dispatches (the read side is
 * {@see ScopesToPosition}). Operations (platform_admin) owns the
 * whole lifecycle up to Dispatched; only the rep currently holding the destination
 * position may Accept. Superuser is granted everything via Shield's Gate::before.
 *
 * Read abilities return true and lean on getEloquentQuery() scope to narrow rows.
 */
class StockDispatchPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockDispatch $stockDispatch): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, StockDispatch $stockDispatch): bool
    {
        return $this->isEditableByOperations($user, $stockDispatch);
    }

    public function delete(User $user, StockDispatch $stockDispatch): bool
    {
        return $this->isEditableByOperations($user, $stockDispatch);
    }

    public function send(User $user, StockDispatch $stockDispatch): bool
    {
        return $this->isEditableByOperations($user, $stockDispatch);
    }

    public function void(User $user, StockDispatch $stockDispatch): bool
    {
        return $user->hasRole('platform_admin')
            && in_array($stockDispatch->status, [StockDispatchStatus::Draft, StockDispatchStatus::Dispatched], true);
    }

    public function accept(User $user, StockDispatch $stockDispatch): bool
    {
        if (! $user->hasRole('sales_rep') || $stockDispatch->status !== StockDispatchStatus::Dispatched) {
            return false;
        }

        return app(RepScope::class)->activePositions($user)->contains('id', $stockDispatch->position_id);
    }

    private function isEditableByOperations(User $user, StockDispatch $stockDispatch): bool
    {
        return $user->hasRole('platform_admin') && $stockDispatch->status === StockDispatchStatus::Draft;
    }
}
