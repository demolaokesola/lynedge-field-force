<?php

namespace App\Models;

use App\Models\Concerns\ScopesToTerritory;
use Database\Factories\DemandCreatorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person/place who drives demand for products (referenced by Calls in a later
 * phase), categorised by {@see DemandCreatorType} and anchored to a territory.
 * created_by tracks which rep (if any) added it from the field panel — never
 * mass-assign it, it is set server-side.
 */
#[Fillable(['demand_creator_type_id', 'territory_id', 'name', 'affiliation', 'phone', 'address'])]
class DemandCreator extends Model
{
    /** @use HasFactory<DemandCreatorFactory> */
    use HasFactory, ScopesToTerritory;

    /**
     * @return BelongsTo<DemandCreatorType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DemandCreatorType::class, 'demand_creator_type_id');
    }

    /**
     * @return BelongsTo<Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
