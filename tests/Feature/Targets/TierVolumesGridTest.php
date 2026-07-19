<?php

use App\Filament\Office\Resources\TargetTiers\Pages\TierVolumesGrid;
use App\Jobs\RebuildRepMonthlyTargetsJob;
use App\Models\Cycle;
use App\Models\Product;
use App\Models\TargetAssignment;
use App\Models\TargetTier;
use App\Models\TargetTierLine;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->withRole('platform_admin')->create());
});

test('the grid pre-fills existing annual volumes', function (): void {
    $product = Product::factory()->create();
    $tier = TargetTier::factory()->create();
    TargetTierLine::factory()->create([
        'target_tier_id' => $tier->id,
        'product_id' => $product->id,
        'annual_volume' => 1200,
    ]);

    livewire(TierVolumesGrid::class)
        ->assertSchemaStateSet(function (array $state) use ($product, $tier): void {
            $row = collect($state['volumes'])->firstWhere('product_id', $product->id);

            expect($row)->not->toBeNull();
            expect($row["tier_{$tier->id}"])->toBe(1200.0);
        });
});

test('editing a cell and saving persists the new value', function (): void {
    $product = Product::factory()->create();
    $tier = TargetTier::factory()->create();

    $component = livewire(TierVolumesGrid::class);
    $rowKey = array_key_first($component->instance()->form->getRawState()['volumes']);

    $component
        ->fillForm([
            'volumes' => [$rowKey => ["tier_{$tier->id}" => '1500']],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('target_tier_lines', [
        'target_tier_id' => $tier->id,
        'product_id' => $product->id,
        'annual_volume' => 1500,
    ]);
});

test('clearing a cell deletes the underlying line', function (): void {
    $product = Product::factory()->create();
    $tier = TargetTier::factory()->create();
    TargetTierLine::factory()->create([
        'target_tier_id' => $tier->id,
        'product_id' => $product->id,
        'annual_volume' => 1200,
    ]);

    $component = livewire(TierVolumesGrid::class);
    $rowKey = array_key_first($component->instance()->form->getRawState()['volumes']);

    $component
        ->fillForm([
            'volumes' => [$rowKey => ["tier_{$tier->id}" => '']],
        ])
        ->call('save');

    $this->assertDatabaseMissing('target_tier_lines', [
        'target_tier_id' => $tier->id,
        'product_id' => $product->id,
    ]);
});

test('negative and non-numeric values are rejected', function (): void {
    $product = Product::factory()->create();
    $tier = TargetTier::factory()->create();

    $component = livewire(TierVolumesGrid::class);
    $rowKey = array_key_first($component->instance()->form->getRawState()['volumes']);

    $component
        ->fillForm([
            'volumes' => [$rowKey => ["tier_{$tier->id}" => '-5']],
        ])
        ->call('save')
        ->assertHasFormErrors(["volumes.{$rowKey}.tier_{$tier->id}"]);

    $component = livewire(TierVolumesGrid::class);
    $rowKey = array_key_first($component->instance()->form->getRawState()['volumes']);

    $component
        ->fillForm([
            'volumes' => [$rowKey => ["tier_{$tier->id}" => 'not-a-number']],
        ])
        ->call('save')
        ->assertHasFormErrors(["volumes.{$rowKey}.tier_{$tier->id}"]);
});

test('saving dispatches a rebuild only for reps assigned a changed tier', function (): void {
    $product = Product::factory()->create();
    $changedTier = TargetTier::factory()->create();
    $untouchedTier = TargetTier::factory()->create();

    $rep = User::factory()->withRole('sales_rep')->create();
    $cycle = Cycle::factory()->create();

    TargetAssignment::factory()->create([
        'user_id' => $rep->id,
        'cycle_id' => $cycle->id,
        'target_tier_id' => $changedTier->id,
    ]);

    TargetAssignment::factory()->create([
        'target_tier_id' => $untouchedTier->id,
    ]);

    // Fake after the setup writes above so only the grid save's own dispatch
    // (not TargetAssignmentObserver's create-time dispatch) is asserted.
    Bus::fake();

    $component = livewire(TierVolumesGrid::class);
    $rowKey = array_key_first($component->instance()->form->getRawState()['volumes']);

    $component
        ->fillForm([
            'volumes' => [$rowKey => ["tier_{$changedTier->id}" => '2000']],
        ])
        ->call('save');

    Bus::assertDispatchedTimes(RebuildRepMonthlyTargetsJob::class, 1);
    Bus::assertDispatched(
        RebuildRepMonthlyTargetsJob::class,
        fn (RebuildRepMonthlyTargetsJob $job): bool => $job->userId === $rep->id && $job->cycleId === $cycle->id,
    );
});
