<?php

namespace App\Services;

use App\Enums\DistributionStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidStockTransition;
use App\Models\Distribution;
use App\Models\PositionProductStock;
use App\Models\User;
use App\Policies\DistributionPolicy;
use Illuminate\Support\Facades\DB;

/**
 * The single place a distribution is posted. Posting freezes the invoice and — once
 * {@see StockSettings::consumptionEnabled()} is on — draws each line down from the
 * position's stock through {@see StockLedger}.
 *
 * Distributions can only be voided while Draft ({@see DistributionPolicy}),
 * so there is no reversal path: a posted distribution's movements are final.
 */
class DistributionPostingService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly StockSettings $settings,
    ) {}

    /**
     * Lines that would take the position's balance below zero, for the confirmation
     * modal and for the hard block. Empty when consumption is off.
     *
     * @return list<array{product: string, available: string, requested: string}>
     */
    public function shortfalls(Distribution $distribution): array
    {
        if (! $this->settings->consumptionEnabled()) {
            return [];
        }

        $distribution->loadMissing('lines.product');

        $balances = PositionProductStock::query()
            ->where('position_id', $distribution->position_id)
            ->whereIn('product_id', $distribution->lines->pluck('product_id'))
            ->pluck('quantity', 'product_id');

        $shortfalls = [];

        foreach ($distribution->lines as $line) {
            $available = (string) ($balances[$line->product_id] ?? '0');

            if (bccomp(bcsub($available, (string) $line->quantity, 2), '0', 2) < 0) {
                $shortfalls[] = [
                    'product' => $line->product->name,
                    'available' => $available,
                    'requested' => (string) $line->quantity,
                ];
            }
        }

        return $shortfalls;
    }

    /**
     * @throws InvalidStockTransition when the distribution is not currently Draft.
     * @throws InsufficientStock when block_negative_stock is on and any line is short.
     */
    public function post(Distribution $distribution, User $postedBy): void
    {
        DB::transaction(function () use ($distribution, $postedBy): void {
            $locked = Distribution::query()
                ->whereKey($distribution->getKey())
                ->lockForUpdate()
                ->with(['lines.product', 'position'])
                ->firstOrFail();

            if ($locked->status !== DistributionStatus::Draft) {
                throw new InvalidStockTransition("Distribution {$locked->getKey()} is {$locked->status->value}, not draft.");
            }

            if ($this->settings->consumptionEnabled()) {
                $shortfalls = $this->shortfalls($locked);

                if ($shortfalls !== [] && $this->settings->blockNegativeStock()) {
                    throw new InsufficientStock($shortfalls);
                }

                foreach ($locked->lines as $line) {
                    $this->ledger->record(
                        $locked->position,
                        $line->product,
                        bcmul((string) $line->quantity, '-1', 2),
                        StockMovementType::Distribution,
                        $line,
                        $postedBy,
                        $locked->invoice_date,
                    );
                }
            }

            $locked->status = DistributionStatus::Posted;
            $locked->save();

            $distribution->setRawAttributes($locked->getAttributes(), sync: true);
        });
    }
}
