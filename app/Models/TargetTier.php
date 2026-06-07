<?php

namespace App\Models;

use Database\Factories\TargetTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'active'])]
class TargetTier extends Model
{
    /** @use HasFactory<TargetTierFactory> */
    use HasFactory;

    /**
     * @return HasMany<TargetTierLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(TargetTierLine::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
