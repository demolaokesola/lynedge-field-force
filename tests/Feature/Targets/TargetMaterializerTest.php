<?php

use App\Enums\AssignmentReason;
use App\Enums\TargetBasis;
use App\Models\Cycle;
use App\Models\Product;
use App\Models\RepMonthlyTarget;
use App\Models\TargetAssignment;
use App\Models\TargetAssignmentLine;
use App\Models\TargetTier;
use App\Models\TargetTierLine;
use App\Models\User;
use App\Services\AttainmentService;
use App\Services\TargetMaterializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Worked examples from docs/playbook.md §3.4.
 *
 * Cycle: 2025-02-01 → 2026-01-31 (12 months, even split → 1/12 per month).
 * Divisor is ALWAYS 12, never the span length.
 */
test('maternity leave produces effective annual 900 and YTD at end-of-Aug is 400', function (): void {
    $cycle = Cycle::factory()->create([
        'name' => '2025/2026',
        'starts_on' => '2025-02-01',
        'ends_on' => '2026-01-31',
    ]);

    $product = Product::factory()->create();
    $rep = User::factory()->withRole('sales_rep')->create();

    $tier2 = TargetTier::factory()->create(['name' => 'Tier 2']);
    TargetTierLine::factory()->create([
        'target_tier_id' => $tier2->id,
        'product_id' => $product->id,
        'annual_volume' => 1200,
    ]);

    // Tier 2 for Feb–Apr
    TargetAssignment::factory()->create([
        'cycle_id' => $cycle->id,
        'user_id' => $rep->id,
        'target_tier_id' => $tier2->id,
        'basis' => TargetBasis::Tier,
        'effective_from' => '2025-02-01',
        'effective_to' => '2025-04-30',
        'reason' => AssignmentReason::Initial,
    ]);

    // Maternity May–Jul: custom 0/yr
    $maternity = TargetAssignment::factory()->create([
        'cycle_id' => $cycle->id,
        'user_id' => $rep->id,
        'target_tier_id' => null,
        'basis' => TargetBasis::Custom,
        'effective_from' => '2025-05-01',
        'effective_to' => '2025-07-31',
        'reason' => AssignmentReason::Maternity,
    ]);
    TargetAssignmentLine::factory()->create([
        'target_assignment_id' => $maternity->id,
        'product_id' => $product->id,
        'annual_volume' => 0,
    ]);

    // Tier 2 for Aug–Jan (no end date)
    TargetAssignment::factory()->create([
        'cycle_id' => $cycle->id,
        'user_id' => $rep->id,
        'target_tier_id' => $tier2->id,
        'basis' => TargetBasis::Tier,
        'effective_from' => '2025-08-01',
        'effective_to' => null,
        'reason' => AssignmentReason::Adjustment,
    ]);

    app(TargetMaterializer::class)->rebuild($rep, $cycle);

    // Effective annual = 9 × 100 + 3 × 0 = 900
    $effectiveAnnual = RepMonthlyTarget::where('cycle_id', $cycle->id)
        ->where('user_id', $rep->id)
        ->where('product_id', $product->id)
        ->sum('target_qty');

    expect((float) $effectiveAnnual)->toBe(900.0);

    // YTD end-of-Aug: Feb(100)+Mar(100)+Apr(100)+May(0)+Jun(0)+Jul(0)+Aug(100) = 400
    $ytd = app(AttainmentService::class)
        ->targetYtd($rep, $cycle, $product, Carbon::parse('2025-08-31'));

    expect($ytd)->toBe(400.0);
});

test('mid-cycle tier change 1200 to 1500 effective Aug produces effective annual 1350', function (): void {
    $cycle = Cycle::factory()->create([
        'name' => '2025/2026',
        'starts_on' => '2025-02-01',
        'ends_on' => '2026-01-31',
    ]);

    $product = Product::factory()->create();
    $rep = User::factory()->withRole('sales_rep')->create();

    $tier2 = TargetTier::factory()->create(['name' => 'Tier 2']);
    TargetTierLine::factory()->create([
        'target_tier_id' => $tier2->id,
        'product_id' => $product->id,
        'annual_volume' => 1200,
    ]);

    $tier3 = TargetTier::factory()->create(['name' => 'Tier 3']);
    TargetTierLine::factory()->create([
        'target_tier_id' => $tier3->id,
        'product_id' => $product->id,
        'annual_volume' => 1500,
    ]);

    // Tier 2 for Feb–Jul (6 months)
    TargetAssignment::factory()->create([
        'cycle_id' => $cycle->id,
        'user_id' => $rep->id,
        'target_tier_id' => $tier2->id,
        'basis' => TargetBasis::Tier,
        'effective_from' => '2025-02-01',
        'effective_to' => '2025-07-31',
        'reason' => AssignmentReason::Initial,
    ]);

    // Tier 3 for Aug–Jan (6 months)
    TargetAssignment::factory()->create([
        'cycle_id' => $cycle->id,
        'user_id' => $rep->id,
        'target_tier_id' => $tier3->id,
        'basis' => TargetBasis::Tier,
        'effective_from' => '2025-08-01',
        'effective_to' => null,
        'reason' => AssignmentReason::TierChange,
    ]);

    app(TargetMaterializer::class)->rebuild($rep, $cycle);

    // Effective annual = (6 × 1200/12) + (6 × 1500/12) = 600 + 750 = 1350
    $effectiveAnnual = RepMonthlyTarget::where('cycle_id', $cycle->id)
        ->where('user_id', $rep->id)
        ->where('product_id', $product->id)
        ->sum('target_qty');

    expect((float) $effectiveAnnual)->toBe(1350.0);

    // Pre-Aug months stay at 100/mo (1200 / 12)
    $febTarget = RepMonthlyTarget::where('cycle_id', $cycle->id)
        ->where('user_id', $rep->id)
        ->where('product_id', $product->id)
        ->where('year_month', '2025-02-01')
        ->value('target_qty');

    expect((float) $febTarget)->toBe(100.0);

    // Aug onwards = 125/mo (1500 / 12)
    $augTarget = RepMonthlyTarget::where('cycle_id', $cycle->id)
        ->where('user_id', $rep->id)
        ->where('product_id', $product->id)
        ->where('year_month', '2025-08-01')
        ->value('target_qty');

    expect((float) $augTarget)->toBe(125.0);
});
