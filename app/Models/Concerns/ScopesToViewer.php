<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts a query to the rows a given viewer is allowed to see.
 *
 * Stub for now — visibility is the read-side counterpart to write-side Policies and
 * stays deliberately separate from them (see .ai/project). Each model that uses this
 * trait will override the body to walk the Region -> Territory -> Position org tree
 * for the viewer's assignments. Until then it is a no-op (returns the query unchanged).
 */
trait ScopesToViewer
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, ?User $viewer = null): Builder
    {
        // TODO: constrain to $viewer's visible org subtree once the domain models exist.
        return $query;
    }
}
