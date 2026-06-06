<?php

namespace App\Services;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Resolves the positions a rep may act under. Phase 5 adds productsForPosition()
 * (the line-level "can't distribute the same product" guard).
 */
class RepScope
{
    /**
     * The rep's active positions in a territory on a given date.
     *
     * A position qualifies when it is active and the rep holds an assignment that has
     * started (effective_from <= $on) and is still open or not yet ended on $on.
     *
     * @return Collection<int, Position>
     */
    public function invoiceablePositions(User $rep, int $territoryId, ?Carbon $on = null): Collection
    {
        $on ??= Carbon::now();

        return Position::query()
            ->where('territory_id', $territoryId)
            ->where('status', PositionStatus::Active)
            ->whereHas('assignments', fn (Builder $q): Builder => $q
                ->where('user_id', $rep->id)
                ->whereDate('effective_from', '<=', $on)
                ->where(fn (Builder $q): Builder => $q
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $on)))
            ->get();
    }
}
