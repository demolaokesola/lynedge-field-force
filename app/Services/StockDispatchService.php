<?php

namespace App\Services;

use App\Enums\StockDispatchStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidStockTransition;
use App\Models\StockDispatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single place a dispatch is accepted into a position's balance. Both the Field
 * list and view actions call this, so the status re-check and row lock live here and
 * not in two copies of a Filament closure.
 */
class StockDispatchService
{
    public function __construct(private readonly StockLedger $ledger) {}

    /**
     * Accept a dispatched shipment: one movement per line, then mark Accepted.
     *
     * @throws InvalidStockTransition when the dispatch is not currently Dispatched —
     *                                typically the second of two racing accepts.
     */
    public function accept(StockDispatch $dispatch, User $acceptedBy): void
    {
        DB::transaction(function () use ($dispatch, $acceptedBy): void {
            $locked = StockDispatch::query()
                ->whereKey($dispatch->getKey())
                ->lockForUpdate()
                ->with(['lines.product', 'position'])
                ->firstOrFail();

            if ($locked->status !== StockDispatchStatus::Dispatched) {
                throw new InvalidStockTransition("Dispatch {$locked->getKey()} is {$locked->status->value}, not dispatched.");
            }

            $acceptedAt = now();

            foreach ($locked->lines as $line) {
                $this->ledger->record(
                    $locked->position,
                    $line->product,
                    (string) $line->quantity,
                    StockMovementType::DispatchAcceptance,
                    $line,
                    $acceptedBy,
                    $acceptedAt,
                );
            }

            $locked->status = StockDispatchStatus::Accepted;
            $locked->accepted_by_user_id = $acceptedBy->id;
            $locked->accepted_at = $acceptedAt;
            $locked->save();

            $dispatch->setRawAttributes($locked->getAttributes(), sync: true);
        });
    }
}
