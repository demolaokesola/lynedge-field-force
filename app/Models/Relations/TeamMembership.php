<?php

namespace App\Models\Relations;

use App\Exceptions\StrictTeamConflictException;
use App\Models\Product;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The product↔team membership relation, guarded so a product can never end up in
 * more than one strict team (see {@see Product::teams()} and the domain invariant in
 * docs/playbook.md §2.0). Because the guard rides on attach/sync, it holds for
 * seeders, factories, tinker, and Filament alike — not just the form.
 *
 * @extends BelongsToMany<Team, Product>
 */
class TeamMembership extends BelongsToMany
{
    /**
     * @param  mixed  $id
     */
    public function attach($id, array $attributes = [], $touch = true): void
    {
        $this->guardStrictPartition($this->resultingTeamIds($id, withExisting: true));

        parent::attach($id, $attributes, $touch);
    }

    /**
     * @param  mixed  $ids
     * @return array<string, array<int, mixed>>
     */
    public function sync($ids, $detaching = true)
    {
        // With $detaching, sync() replaces the whole set; otherwise it adds to it.
        $this->guardStrictPartition($this->resultingTeamIds($ids, withExisting: ! $detaching));

        return parent::sync($ids, $detaching);
    }

    /**
     * The team ids the product would belong to once this operation completes.
     *
     * @param  mixed  $ids
     * @return array<int, int|string>
     */
    protected function resultingTeamIds($ids, bool $withExisting): array
    {
        $incoming = $this->parseIds($ids);

        if ($withExisting) {
            $incoming = array_merge(
                $this->newPivotQuery()->pluck($this->relatedPivotKey)->all(),
                $incoming,
            );
        }

        return array_values(array_unique($incoming));
    }

    /**
     * @param  array<int, int|string>  $teamIds
     */
    protected function guardStrictPartition(array $teamIds): void
    {
        if (Product::wouldExceedOneStrictTeam($teamIds)) {
            throw StrictTeamConflictException::make();
        }
    }
}
