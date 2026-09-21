<?php

namespace App\Services;

use App\Enums\StockCountKind;
use App\Enums\StockCountStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidStockTransition;
use App\Models\PositionProductStock;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single place a stock count changes status. Submitting only locks the count for
 * Operations to review; posting snapshots each line's system balance, records the
 * variance to the ledger, and freezes the count.
 */
class StockCountService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly StockNotifier $notifier,
    ) {}

    /**
     * Hand a draft count to Operations for review.
     *
     * @throws InvalidStockTransition when the count is not currently Draft.
     */
    public function submit(StockCount $count): void
    {
        DB::transaction(function () use ($count): void {
            $locked = $this->lock($count);

            if ($locked->status !== StockCountStatus::Draft) {
                throw new InvalidStockTransition("Stock count {$locked->getKey()} is {$locked->status->value}, not draft.");
            }

            $locked->status = StockCountStatus::Submitted;
            $locked->submitted_at = now();
            $locked->save();

            $count->setRawAttributes($locked->getAttributes(), sync: true);
        });

        $this->notifier->countSubmitted($count);
    }

    /**
     * Reconcile the count against the ledger. Each line's system quantity is read at
     * this moment (not when the rep counted), so the variance is exactly what brings
     * the balance to the counted figure. Opening counts additionally zero any product
     * in the position's catalogue that was not counted; periodic counts leave
     * uncounted products alone.
     *
     * A Submitted count may always be posted. A Draft count may be posted directly
     * only when it is an Opening count, so Operations does not have to submit to
     * themselves.
     *
     * @throws InvalidStockTransition when the count cannot be posted from its status.
     */
    public function post(StockCount $count, User $postedBy): void
    {
        DB::transaction(function () use ($count, $postedBy): void {
            $locked = $this->lock($count);

            $postable = $locked->status === StockCountStatus::Submitted
                || ($locked->status === StockCountStatus::Draft && $locked->kind === StockCountKind::Opening);

            if (! $postable) {
                throw new InvalidStockTransition("Stock count {$locked->getKey()} is {$locked->status->value} and cannot be posted.");
            }

            if ($locked->kind === StockCountKind::Opening) {
                $this->addZeroLinesForUncountedProducts($locked);
            }

            $balances = PositionProductStock::query()
                ->where('position_id', $locked->position_id)
                ->pluck('quantity', 'product_id');

            foreach ($locked->lines as $line) {
                $system = (string) ($balances[$line->product_id] ?? '0');
                $variance = bcsub((string) $line->counted_quantity, $system, 2);

                $line->system_quantity = $system;
                $line->variance_quantity = $variance;
                $line->save();

                if (bccomp($variance, '0', 2) === 0) {
                    continue;
                }

                $this->ledger->record(
                    $locked->position,
                    $line->product,
                    $variance,
                    StockMovementType::StockCount,
                    $line,
                    $postedBy,
                    $locked->count_date,
                );
            }

            $locked->status = StockCountStatus::Posted;
            $locked->submitted_at ??= now();
            $locked->posted_by_user_id = $postedBy->id;
            $locked->posted_at = now();
            $locked->save();

            $count->setRawAttributes($locked->getAttributes(), sync: true);
        });
    }

    /**
     * @throws InvalidStockTransition when the count is already Posted or Void.
     */
    public function void(StockCount $count): void
    {
        DB::transaction(function () use ($count): void {
            $locked = $this->lock($count);

            if (! in_array($locked->status, [StockCountStatus::Draft, StockCountStatus::Submitted], true)) {
                throw new InvalidStockTransition("Stock count {$locked->getKey()} is {$locked->status->value} and cannot be voided.");
            }

            $locked->status = StockCountStatus::Void;
            $locked->save();

            $count->setRawAttributes($locked->getAttributes(), sync: true);
        });
    }

    private function lock(StockCount $count): StockCount
    {
        return StockCount::query()
            ->whereKey($count->getKey())
            ->lockForUpdate()
            ->with(['lines.product', 'position.team.products'])
            ->firstOrFail();
    }

    /**
     * An opening count is a complete statement of what the position holds, so a
     * catalogue product with no line was counted as zero.
     */
    private function addZeroLinesForUncountedProducts(StockCount $count): void
    {
        $counted = $count->lines->pluck('product_id');

        $uncounted = app(RepScope::class)
            ->productsForPosition($count->position)
            ->reject(fn ($product): bool => $counted->contains($product->id));

        foreach ($uncounted as $product) {
            $count->lines()->save(new StockCountLine([
                'product_id' => $product->id,
                'counted_quantity' => '0.00',
            ]));
        }

        $count->load('lines.product');
    }
}
