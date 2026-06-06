<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\DistributionStatus;
use App\Models\Concerns\ScopesToViewer;
use Database\Factories\DistributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single-team invoice created under one of the rep's active positions.
 * team_id and territory_id are denormalised at write time (reorg history).
 * total_amount is always recomputed server-side from the lines.
 *
 * Never mass-assign: user_id, territory_id, team_id, status, total_amount.
 */
#[Fillable(['position_id', 'customer_id', 'invoice_number', 'invoice_date', 'notes'])]
class Distribution extends Model
{
    /** @use HasFactory<DistributionFactory> */
    use HasFactory, ScopesToViewer;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<DistributionLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DistributionLine::class);
    }

    /**
     * Recompute total_amount from the current set of lines. Called by the DistributionLine
     * observer after each line is saved or deleted so the parent is always in sync.
     */
    public function refreshTotal(): void
    {
        $this->total_amount = $this->lines()->sum('line_amount');
        $this->saveQuietly();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => MoneyCast::class,
            'invoice_date' => 'date',
            'status' => DistributionStatus::class,
        ];
    }
}
