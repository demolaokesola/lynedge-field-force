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
        return $this->activePositions($rep, $on)
            ->where('territory_id', $territoryId)
            ->values();
    }

    /**
     * The rep's active positions across ALL territories on a given date — the union the
     * field form offers as the position a call is logged under, and the basis for the
     * "block if the rep has no active position" guard.
     *
     * @return Collection<int, Position>
     */
    public function activePositions(User $rep, ?Carbon $on = null): Collection
    {
        $on ??= Carbon::now();

        return Position::query()
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
