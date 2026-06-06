<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Database\Factories\DistributionLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line on a Distribution invoice.
 * line_amount = quantity * unit_price — computed in the saving observer; never trusted from a form.
 * total_amount on the parent Distribution is refreshed after each line save/delete.
 */
#[Fillable(['distribution_id', 'product_id', 'quantity', 'unit_price', 'line_amount'])]
class DistributionLine extends Model
{
    /** @use HasFactory<DistributionLineFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // Recompute line_amount before every INSERT or UPDATE — client values are ignored.
        static::saving(function (DistributionLine $line): void {
            $line->line_amount = bcmul((string) $line->quantity, (string) $line->unit_price, 2);
        });

        // Keep parent total in sync after each line change.
        static::saved(function (DistributionLine $line): void {
            $line->distribution->refreshTotal();
        });

        static::deleted(function (DistributionLine $line): void {
            $line->distribution->refreshTotal();
        });
    }

    /**
     * @return BelongsTo<Distribution, $this>
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
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
            'unit_price' => MoneyCast::class,
            'line_amount' => MoneyCast::class,
            'quantity' => 'decimal:2',
        ];
    }
}
