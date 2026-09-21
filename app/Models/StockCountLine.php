<?php

namespace App\Models;

use App\Services\StockCountService;
use Database\Factories\StockCountLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product on a StockCount. Only counted_quantity comes from the form;
 * system_quantity and variance_quantity are filled by {@see StockCountService} at
 * post time and are null until then. Saving a line has no stock effect.
 */
#[Fillable(['stock_count_id', 'product_id', 'counted_quantity'])]
class StockCountLine extends Model
{
    /** @use HasFactory<StockCountLineFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<StockCount, $this>
     */
    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
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
            'counted_quantity' => 'decimal:2',
            'system_quantity' => 'decimal:2',
            'variance_quantity' => 'decimal:2',
        ];
    }
}
