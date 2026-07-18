<?php

namespace App\Models\Concerns;

use App\Models\Territory;
use App\Models\User;
use App\Services\RepScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Visibility scope for territory-anchored master data that has no personal ownership
 * for READ (unlike {@see ScopesToViewer}, which keys sales_rep visibility off user_id):
 *  - superuser|platform_admin|hq_lead|accountant -> all
 *  - regional_head -> territories of their region_id
 *  - sales_rep -> territories of their own active positions, plus (if supervising any
 *                  position) territories of the positions they supervise
 *  - anyone else / no viewer -> nothing
 *
 * Write-side ownership (who may edit a given row) is a separate concern, handled by the
 * model's created_by column and its policy.
 */
trait ScopesToTerritory
{
    /**
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
            return $query->whereIn('territory_id', Territory::query()
                ->where('region_id', $viewer->region_id)
                ->select('id'));
        }

        if ($viewer->hasRole('sales_rep')) {
            $repScope = app(RepScope::class);
            $territoryIds = $repScope->activePositions($viewer)->pluck('territory_id')
                ->merge($repScope->positionsSupervisedBy($viewer)->pluck('territory_id'))
                ->unique();

            return $query->whereIn('territory_id', $territoryIds);
        }

        return $query->whereRaw('1 = 0');
    }
}
