<?php

namespace App\Models;

use App\Enums\StockCountKind;
use App\Enums\StockCountStatus;
use App\Models\Concerns\ScopesToPosition;
use App\Services\StockCountService;
use Database\Factories\StockCountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A physical count of a position's stock, reconciled against the ledger when posted.
 * Two kinds share the model: an Opening count sets starting balances at go-live
 * (Operations creates and posts it directly), a Periodic count is the rep's routine
 * check (rep submits, Operations posts). team_id and territory_id are denormalised at
 * write time (reorg history), same as {@see StockDispatch}.
 *
 * Draft -> Submitted -> Posted, with Void possible from Draft or Submitted. Posting is
 * the only point at which stock actually moves — {@see StockCountService} snapshots
 * each line's system quantity and records the variance.
 *
 * Never mass-assign: counted_by_user_id, territory_id, team_id, status, submitted_at,
 * posted_by_user_id, posted_at.
 */
#[Fillable(['position_id', 'kind', 'count_date', 'notes'])]
class StockCount extends Model
{
    /** @use HasFactory<StockCountFactory> */
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
    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }

    /**
     * @return HasMany<StockCountLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => StockCountKind::class,
            'count_date' => 'date',
            'status' => StockCountStatus::class,
            'submitted_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }
}
