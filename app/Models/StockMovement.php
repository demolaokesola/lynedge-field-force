<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Concerns\ScopesToPosition;
use App\Services\StockLedger;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Immutable ledger row — the audit trail for every stock change. Only ever inserted by
 * {@see StockLedger}, never via a Filament form and never updated or
 * deleted. team_id and territory_id are denormalised from the position at write time,
 * same as {@see Distribution}.
 *
 * source is the StockDispatchLine or StockAdjustmentLine that caused this movement.
 */
#[Fillable(['position_id', 'territory_id', 'team_id', 'product_id', 'quantity_delta', 'type', 'caused_by_user_id'])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory, ScopesToPosition;

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function causedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caused_by_user_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:2',
            'type' => StockMovementType::class,
        ];
    }
}
