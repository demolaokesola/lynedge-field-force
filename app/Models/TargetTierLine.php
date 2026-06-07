<?php

namespace App\Models;

use Database\Factories\TargetTierLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['target_tier_id', 'product_id', 'annual_volume'])]
class TargetTierLine extends Model
{
    /** @use HasFactory<TargetTierLineFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<TargetTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(TargetTier::class, 'target_tier_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annual_volume' => 'decimal:2',
        ];
    }
}
