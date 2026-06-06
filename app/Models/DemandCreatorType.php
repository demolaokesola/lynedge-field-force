<?php

namespace App\Models;

use Database\Factories\DemandCreatorTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lookup of demand-creator categories (Hospital Pharmacist, Doctor/CHEW, …). A DB
 * table rather than an enum so admins can extend the list at runtime.
 */
#[Fillable(['name'])]
class DemandCreatorType extends Model
{
    /** @use HasFactory<DemandCreatorTypeFactory> */
    use HasFactory;

    /**
     * @return HasMany<DemandCreator, $this>
     */
    public function demandCreators(): HasMany
    {
        return $this->hasMany(DemandCreator::class);
    }
}
