<?php

namespace App\Models;

use Database\Factories\TargetAssignmentLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['target_assignment_id', 'product_id', 'annual_volume'])]
class TargetAssignmentLine extends Model
{
    /** @use HasFactory<TargetAssignmentLineFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<TargetAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TargetAssignment::class, 'target_assignment_id');
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
