<?php

namespace App\Services;

use App\Enums\DistributionStatus;
use App\Models\Cycle;
use App\Models\DistributionLine;
use App\Models\Product;
use App\Models\RepMonthlyTarget;
use App\Models\User;
use Illuminate\Support\Carbon;

class AttainmentService
{
    /**
     * Sum of materialised monthly targets up to and including the month of $asOf.
     */
    public function targetYtd(User $rep, Cycle $cycle, Product $product, Carbon $asOf): float
    {
        return (float) RepMonthlyTarget::query()
            ->where('cycle_id', $cycle->id)
            ->where('user_id', $rep->id)
            ->where('product_id', $product->id)
            ->where('year_month', '<=', $asOf->copy()->startOfMonth())
            ->sum('target_qty');
    }

    /**
     * Sum of posted distribution quantities from cycle start through $asOf.
     */
    public function actualYtd(User $rep, Cycle $cycle, Product $product, Carbon $asOf): float
    {
        return (float) DistributionLine::query()
            ->whereHas('distribution', fn ($q) => $q
                ->where('user_id', $rep->id)
                ->where('status', DistributionStatus::Posted)
                ->whereBetween('invoice_date', [$cycle->starts_on, $asOf]))
            ->where('product_id', $product->id)
            ->sum('quantity');
    }

    /**
     * Attainment percentage, or null when the target is zero (avoid division by zero).
     */
    public function attainmentPct(float $targetYtd, float $actualYtd): ?float
    {
        if ($targetYtd <= 0) {
            return null;
        }

        return round($actualYtd / $targetYtd * 100, 1);
    }

    /**
     * Sum of materialised monthly targets for the full cycle.
     */
    public function targetFullCycle(User $rep, Cycle $cycle, Product $product): float
    {
        return (float) RepMonthlyTarget::query()
            ->where('cycle_id', $cycle->id)
            ->where('user_id', $rep->id)
            ->where('product_id', $product->id)
            ->sum('target_qty');
    }
}
