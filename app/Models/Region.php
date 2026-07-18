<?php

namespace App\Models;

use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code'])]
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    /**
     * @return HasMany<Territory, $this>
     */
    public function territories(): HasMany
    {
        return $this->hasMany(Territory::class);
    }

    /**
     * Restrict to the regions a viewer may see in the org tree.
     *
     * National roles see all; a regional_head sees only their anchored region;
     * sales_reps (supervising or not) are anchored by their Position (Phase 2) and see none here.
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
            return $query->whereKey($user->region_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
