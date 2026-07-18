<?php

namespace App\Models;

use App\Models\Concerns\ScopesToPosition;
use App\Services\RepScope;
use App\Services\StockLedger;
use Database\Factories\PositionProductStockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The materialised current stock balance for a (position, product) pair — always
 * recomputed from {@see StockMovement} by {@see StockLedger}, never
 * written directly. May go negative (distribution is not yet blocked by insufficient
 * stock).
 *
 * Unlike the transactional stock models this is CURRENT state, not a point-in-time
 * record, so it deliberately does not denormalise territory_id — scoping joins live
 * through position, which must always reflect the position's current territory.
 *
 * Fillable only so {@see StockLedger} can updateOrCreate() — no Filament
 * form ever writes this model, so the mass-assignment guard protects nothing here.
 */
#[Fillable(['position_id', 'product_id', 'quantity'])]
class PositionProductStock extends Model
{
    /** @use HasFactory<PositionProductStockFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Restrict to the balances a viewer may see. Position-anchored like
     * {@see ScopesToPosition}, but joins live through position
     * since this table has no denormalised territory_id.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, ?User $viewer = null): Builder
    {
        $viewer ??= auth()->user();

        if ($viewer === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($viewer->hasAnyRole(['superuser', 'platform_admin', 'hq_lead', 'accountant'])) {
            return $query;
        }

        if ($viewer->hasRole('regional_head')) {
            return $query->whereHas(
                'position.territory',
                fn (Builder $q): Builder => $q->where('region_id', $viewer->region_id),
            );
        }

        if ($viewer->hasRole('sales_rep')) {
            return $query->whereIn('position_id', app(RepScope::class)->positionIdsHeldOrSupervisedBy($viewer));
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }
}
