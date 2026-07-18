<?php

namespace App\Models;

use App\Enums\StockAdjustmentReason;
use Database\Factories\StockAdjustmentLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line on a StockAdjustment. quantity_delta is signed — positive adds stock
 * (e.g. a recount correction upward), negative removes it (damage, loss, recall,
 * return). Saving a line has no stock effect — stock only moves when the parent
 * adjustment is posted (see {@see StockAdjustment}).
 */
#[Fillable(['stock_adjustment_id', 'product_id', 'quantity_delta', 'reason', 'note'])]
class StockAdjustmentLine extends Model
{
    /** @use HasFactory<StockAdjustmentLineFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<StockAdjustment, $this>
     */
    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
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
            'quantity_delta' => 'decimal:2',
            'reason' => StockAdjustmentReason::class,
        ];
    }
}
