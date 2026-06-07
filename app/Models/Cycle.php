<?php

namespace App\Models;

use Database\Factories\CycleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_on', 'ends_on', 'is_current'])]
class Cycle extends Model
{
    /** @use HasFactory<CycleFactory> */
    use HasFactory;

    /**
     * @return HasMany<TargetAssignment, $this>
     */
    public function targetAssignments(): HasMany
    {
        return $this->hasMany(TargetAssignment::class);
    }

    /**
     * @return HasMany<RepMonthlyTarget, $this>
     */
    public function repMonthlyTargets(): HasMany
    {
        return $this->hasMany(RepMonthlyTarget::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }
}
