<?php

namespace App\Models;

use App\Enums\PositionStatus;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The atomic manned unit: a (territory, team) pair.
 *
 * `code` and `enforce_team_uniqueness` are both observer-managed (`code` is derived
 * from territory.code + team.code; `enforce_team_uniqueness` mirrors
 * territory.team_policy === strict) and are deliberately NOT mass-assignable — never
 * let the client set them.
 */
#[Fillable(['territory_id', 'team_id', 'label', 'status'])]
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

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
     * @return HasMany<PositionAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PositionAssignment::class);
    }

    /**
     * The current open occupancy, if any (effective_to IS NULL).
     *
     * @return HasOne<PositionAssignment, $this>
     */
    public function openAssignment(): HasOne
    {
        return $this->hasOne(PositionAssignment::class)->whereNull('effective_to');
    }

    /**
     * Restrict to the positions a viewer may see in the org tree.
     *
     * National roles see all; a regional_head sees positions in their region's
     * territories; reps/supervisors are anchored by their Position and see none here.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleOrgTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['superuser', 'platform_admin', 'hq_lead', 'accountant'])) {
            return $query;
        }

        if ($user->hasRole('regional_head')) {
            return $query->whereHas('territory', fn (Builder $q) => $q->where('region_id', $user->region_id));
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enforce_team_uniqueness' => 'boolean',
            'status' => PositionStatus::class,
        ];
    }
}
