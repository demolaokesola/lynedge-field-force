<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\TeamKind;
use App\Models\Relations\TeamMembership;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A sellable product. Team membership is the {@see product_team} pivot — there is no
 * team_id column. A product may belong to AT MOST ONE strict team and any number of
 * liberal teams; that invariant is enforced on attach/sync via {@see TeamMembership}.
 */
#[Fillable(['name', 'sku', 'pack_size', 'unit_price', 'active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    /**
     * Whether the given set of team ids contains more than one strict team — i.e.
     * attaching it to a product would break the disjoint-strict-partition invariant.
     * Shared by {@see TeamMembership} (integrity) and the Products form (clean error).
     *
     * @param  array<int, int|string>  $teamIds
     */
    public static function wouldExceedOneStrictTeam(array $teamIds): bool
    {
        return Team::query()
            ->whereKey(array_values(array_filter($teamIds, is_scalar(...))))
            ->where('kind', TeamKind::Strict)
            ->count() > 1;
    }

    /**
     * Route belongsToMany through the guarded {@see TeamMembership} relation.
     */
    protected function newBelongsToMany(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
    ): BelongsToMany {
        return new TeamMembership($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::class,
            'active' => 'boolean',
        ];
    }
}
