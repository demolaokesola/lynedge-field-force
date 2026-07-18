<?php

namespace App\Models;

use Database\Factories\StockDispatchLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line on a StockDispatch. Saving a line has no stock effect — stock only moves
 * when the parent dispatch is accepted (see {@see StockDispatch}), so this model
 * intentionally has no booted() hook, unlike DistributionLine.
 */
#[Fillable(['stock_dispatch_id', 'product_id', 'quantity'])]
class StockDispatchLine extends Model
{
    /** @use HasFactory<StockDispatchLineFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<StockDispatch, $this>
     */
    public function stockDispatch(): BelongsTo
    {
        return $this->belongsTo(StockDispatch::class);
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
            'quantity' => 'decimal:2',
        ];
    }
}
