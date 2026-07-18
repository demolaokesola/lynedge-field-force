<?php

namespace App\Models\Concerns;

use App\Models\Territory;
use App\Models\User;
use App\Services\RepScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Transaction visibility scope for stock documents (a variant of Scope A —
 * {@see ScopesToViewer}). Stock belongs to the POSITION, not to whichever user
 * authored the document (Operations creates dispatches/adjustments on behalf of a
 * position, not themselves), so the sales_rep branch keys off position ownership
 * instead of user_id:
 *
 *  - superuser|platform_admin|hq_lead|accountant -> all
 *  - regional_head -> territories of their region_id
 *  - sales_rep     -> documents for positions they hold or supervise
 *  - anyone else / no viewer -> nothing
 *
 * For models carrying position_id and a denormalised territory_id
 * (StockDispatch, StockAdjustment, StockMovement).
 */
trait ScopesToPosition
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
            return $query->whereIn('territory_id', $this->territoriesInRegion($viewer->region_id));
        }

        if ($viewer->hasRole('sales_rep')) {
            return $query->whereIn('position_id', app(RepScope::class)->positionIdsHeldOrSupervisedBy($viewer));
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * @return Builder<Territory>
     */
    private function territoriesInRegion(?int $regionId): Builder
    {
        return Territory::query()
            ->where('region_id', $regionId)
            ->select('id');
    }
}
