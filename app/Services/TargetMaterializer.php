<?php

namespace App\Services;

use App\Enums\TargetBasis;
use App\Models\Cycle;
use App\Models\RepMonthlyTarget;
use App\Models\TargetAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TargetMaterializer
{
    /**
     * Rebuild rep_monthly_targets for one rep and cycle from scratch.
     *
     * For every calendar month M that falls within the cycle, we find the
     * TargetAssignment active on the 1st of M, resolve per-product annual volumes,
     * and write target_qty = annual / 12.  The divisor is ALWAYS 12 — never the
     * span length — so proration is purely a consequence of which segment is active
     * each month.
     */
    public function rebuild(User $rep, Cycle $cycle): void
    {
        $assignments = TargetAssignment::query()
            ->where('cycle_id', $cycle->id)
            ->where('user_id', $rep->id)
            ->with(['lines', 'tier.lines'])
            ->get();

        $rows = [];

        foreach ($this->cycleMonths($cycle) as $month) {
            $assignment = $this->assignmentForMonth($assignments, $month);

            if ($assignment === null) {
                continue;
            }

            foreach ($this->resolveVolumes($assignment) as $productId => $annual) {
                $rows[] = [
                    'cycle_id' => $cycle->id,
                    'user_id' => $rep->id,
                    'year_month' => $month->toDateString(),
                    'product_id' => $productId,
                    'target_qty' => bcdiv((string) $annual, '12', 6),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        RepMonthlyTarget::query()
            ->where('cycle_id', $cycle->id)
            ->where('user_id', $rep->id)
            ->delete();

        foreach (array_chunk($rows, 200) as $chunk) {
            RepMonthlyTarget::insert($chunk);
        }
    }

    /**
     * @return Carbon[]
     */
    private function cycleMonths(Cycle $cycle): array
    {
        $months = [];
        $cursor = $cycle->starts_on->copy()->startOfMonth();
        $last = $cycle->ends_on->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        return $months;
    }

    private function assignmentForMonth(Collection $assignments, Carbon $month): ?TargetAssignment
    {
        return $assignments->first(function (TargetAssignment $a) use ($month): bool {
            return $a->effective_from->copy()->startOfMonth()->lte($month)
                && ($a->effective_to === null || $a->effective_to->copy()->startOfMonth()->gte($month));
        });
    }

    /**
     * Resolve product_id → annual_volume for an assignment.
     *
     * - basis=Tier:   start with the tier's lines, then let assignment_lines override per-product
     * - basis=Custom: use assignment_lines only
     *
     * @return array<int, float>
     */
    private function resolveVolumes(TargetAssignment $assignment): array
    {
        $volumes = [];

        if ($assignment->basis === TargetBasis::Tier && $assignment->tier !== null) {
            foreach ($assignment->tier->lines as $line) {
                $volumes[$line->product_id] = (float) $line->annual_volume;
            }
        }

        foreach ($assignment->lines as $line) {
            $volumes[$line->product_id] = (float) $line->annual_volume;
        }

        return $volumes;
    }
}
