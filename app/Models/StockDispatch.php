<?php

namespace App\Models;

use App\Enums\StockDispatchStatus;
use App\Models\Concerns\ScopesToPosition;
use App\Services\StockLedger;
use Database\Factories\StockDispatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stock Operations sends to a position. team_id and territory_id are denormalised at
 * write time (reorg history), same as {@see Distribution}.
 *
 * Draft -> Dispatched -> Accepted, with Void possible from Draft or Dispatched.
 * Accepting is the only point at which stock actually moves — handled by
 * {@see StockLedger}, never by a line-level observer, since Draft/
 * Dispatched lines must not affect the position's balance.
 *
 * Never mass-assign: dispatched_by_user_id, territory_id, team_id, status,
 * accepted_by_user_id, accepted_at.
 */
#[Fillable(['position_id', 'dispatch_date', 'notes'])]
class StockDispatch extends Model
{
    /** @use HasFactory<StockDispatchFactory> */
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
    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    /**
     * @return HasMany<StockDispatchLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(StockDispatchLine::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dispatch_date' => 'date',
            'accepted_at' => 'datetime',
            'status' => StockDispatchStatus::class,
        ];
    }
}
