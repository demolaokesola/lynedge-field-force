<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Position;
use App\Models\PositionProductStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The single place stock actually changes. Both dispatch acceptance and adjustment
 * posting record their lines through here, so there is exactly one place that writes
 * an immutable movement and refreshes the materialised balance — mirrors
 * Distribution::refreshTotal()'s recompute-from-source style, applied per
 * (position, product).
 */
class StockLedger
{
    /**
     * Record a stock movement and refresh the position's balance for that product.
     */
    public function record(
        Position $position,
        Product $product,
        string $quantityDelta,
        StockMovementType $type,
        Model $source,
        User $causer,
    ): StockMovement {
        $movement = new StockMovement([
            'position_id' => $position->id,
            'territory_id' => $position->territory_id,
            'team_id' => $position->team_id,
            'product_id' => $product->id,
            'quantity_delta' => $quantityDelta,
            'type' => $type,
            'caused_by_user_id' => $causer->id,
        ]);
        $movement->source()->associate($source);
        $movement->save();

        $this->refreshBalance($position->id, $product->id);

        return $movement;
    }

    /**
     * Recompute a (position, product) balance from the full movement history — never
     * trust an incremental update, always recompute from the ledger.
     */
    private function refreshBalance(int $positionId, int $productId): void
    {
        $quantity = StockMovement::query()
            ->where('position_id', $positionId)
            ->where('product_id', $productId)
            ->sum('quantity_delta');

        PositionProductStock::query()->updateOrCreate(
            ['position_id' => $positionId, 'product_id' => $productId],
            ['quantity' => $quantity],
        );
    }
}
