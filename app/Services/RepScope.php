<?php

namespace App\Services;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Resolves the positions a rep may act under, and the products they may distribute
 * under a given position (the "can't distribute the same product" guard).
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
     * Products the rep may distribute under this position. Loads team.products via
     * the product_team pivot — the sole source of truth for the line-level guard.
     * Works identically for strict and liberal teams.
     *
     * @return Collection<int, Product>
     */
    public function productsForPosition(Position $position): Collection
    {
        return $position->load('team.products')->team->products;
    }

    /**
     * Products the rep may currently see across ALL their active positions — the union
     * of team products, filtered to active=true, for "My Products" in the field panel.
     *
     * @return Builder<Product>
     */
    public function productsForUser(User $rep, ?Carbon $on = null): Builder
    {
        $teamIds = $this->activePositions($rep, $on)->pluck('team_id')->unique();

        return Product::query()
            ->where('active', true)
            ->whereHas('teams', fn (Builder $q): Builder => $q->whereIn('teams.id', $teamIds));
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

    /**
     * The rep's active positions as id => "Code — Territory", for Filament position
     * selects across the field panel (Calls, Customers, Demand Creators).
     *
     * @return array<int, string>
     */
    public function positionOptions(User $rep): array
    {
        return $this->activePositions($rep)
            ->load('territory')
            ->mapWithKeys(fn (Position $position): array => [
                $position->id => "{$position->code} — {$position->territory->name}",
            ])
            ->all();
    }
}
