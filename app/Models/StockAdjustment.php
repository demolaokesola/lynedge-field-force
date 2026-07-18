<?php

namespace App\Models;

use App\Enums\StockAdjustmentStatus;
use App\Models\Concerns\ScopesToPosition;
use App\Services\StockLedger;
use Database\Factories\StockAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Operations correcting a position's stock (damage, loss, correction, return, recall).
 * team_id and territory_id are denormalised at write time, same as {@see Distribution}.
 *
 * Draft -> Posted, with Void possible from Draft only — mirrors DistributionStatus's
 * lifecycle exactly. Posting is the only point at which stock actually moves — handled
 * by {@see StockLedger}, never by a line-level observer.
 *
 * Never mass-assign: adjusted_by_user_id, territory_id, team_id, status.
 */
#[Fillable(['position_id', 'adjustment_date', 'notes'])]
class StockAdjustment extends Model
{
    /** @use HasFactory<StockAdjustmentFactory> */
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
     * @return BelongsTo<User, $this>
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by_user_id');
    }

    /**
     * @return HasMany<StockAdjustmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'status' => StockAdjustmentStatus::class,
        ];
    }
}
