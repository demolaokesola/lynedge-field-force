<?php

namespace App\Models\Concerns;

use App\Models\Territory;
use App\Models\User;
use App\Services\RepScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Transaction visibility scope (Scope A). Restricts a query to the rows a given viewer
 * may READ — the read-side counterpart to write-side Policies, kept deliberately
 * separate from them (see .ai/project, "Two scopes").
 *
 * For models carrying both user_id and territory_id (Call, Distribution, Deposit), so
 * it keys off those two columns:
 *  - superuser|platform_admin|hq_lead|accountant -> all
 *  - regional_head -> territories of their region_id
 *  - sales_rep     -> own activity, plus (if supervising any position) the current
 *                      occupants' activity for the positions they supervise
 *  - anyone else / no viewer -> nothing
 *
 * Supervision is a derived fact (Position.supervisor_id), not a role — a plain rep
 * simply has no subordinates, so this reduces to own-activity-only for them.
 */
trait ScopesToViewer
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
            $userIds = app(RepScope::class)->subordinateUserIds($viewer)->push($viewer->id);

            return $query->whereIn('user_id', $userIds);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Subquery of territory ids in a region. A null region id resolves to no territories
     * (never "all") — a rep with no region must not fall through to everything.
     *
     * @return Builder<Territory>
     */
    private function territoriesInRegion(?int $regionId): Builder
    {
        return Territory::query()
            ->where('region_id', $regionId)
            ->select('id');
    }
}
