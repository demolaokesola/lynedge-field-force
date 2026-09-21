<?php

namespace App\Services;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidStockTransition;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single place an adjustment is posted to the ledger. Both the Office list and
 * edit actions call this, so the status re-check and row lock live here.
 */
class StockAdjustmentService
{
    public function __construct(private readonly StockLedger $ledger) {}

    /**
     * Post a draft adjustment: one signed movement per line, then mark Posted.
     *
     * @throws InvalidStockTransition when the adjustment is not currently Draft.
     */
    public function post(StockAdjustment $adjustment, User $postedBy): void
    {
        DB::transaction(function () use ($adjustment, $postedBy): void {
            $locked = StockAdjustment::query()
                ->whereKey($adjustment->getKey())
                ->lockForUpdate()
                ->with(['lines.product', 'position'])
                ->firstOrFail();

            if ($locked->status !== StockAdjustmentStatus::Draft) {
                throw new InvalidStockTransition("Adjustment {$locked->getKey()} is {$locked->status->value}, not draft.");
            }

            foreach ($locked->lines as $line) {
                $this->ledger->record(
                    $locked->position,
                    $line->product,
                    (string) $line->quantity_delta,
                    StockMovementType::Adjustment,
                    $line,
                    $postedBy,
                    $locked->adjustment_date,
                );
            }

            $locked->status = StockAdjustmentStatus::Posted;
            $locked->save();

            $adjustment->setRawAttributes($locked->getAttributes(), sync: true);
        });
    }
}
