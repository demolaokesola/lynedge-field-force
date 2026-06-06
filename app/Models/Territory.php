<?php

namespace App\Models;

use App\Enums\TeamPolicy;
use Database\Factories\TerritoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['region_id', 'name', 'code', 'team_policy'])]
class Territory extends Model
{
    /** @use HasFactory<TerritoryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Restrict to the territories a viewer may see in the org tree.
     *
     * National roles see all; a regional_head sees only their region's territories;
     * reps/supervisors are anchored by their Position (Phase 2) and see none here.
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
            return $query->where('region_id', $user->region_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team_policy' => TeamPolicy::class,
        ];
    }
}
