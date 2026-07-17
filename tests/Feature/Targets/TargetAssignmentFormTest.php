<?php

use App\Enums\AssignmentReason;
use App\Filament\Office\Resources\TargetAssignments\Pages\CreateTargetAssignment;
use App\Models\Cycle;
use App\Models\TargetTier;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->withRole('platform_admin')->create());
});

test('the tier field is hidden until basis is set to tier', function (): void {
    livewire(CreateTargetAssignment::class)
        ->assertFormFieldHidden('target_tier_id')
        ->fillForm(['basis' => 'tier'])
        ->assertFormFieldVisible('target_tier_id')
        ->fillForm(['basis' => 'custom'])
        ->assertFormFieldHidden('target_tier_id');
});

test('the custom lines repeater is hidden until basis is set to custom', function (): void {
    livewire(CreateTargetAssignment::class)
        ->assertFormFieldHidden('lines')
        ->fillForm(['basis' => 'custom'])
        ->assertFormFieldVisible('lines');
});

test('a tier is required when basis is tier', function (): void {
    $cycle = Cycle::factory()->create();
    $user = User::factory()->withRole('sales_rep')->create();

    livewire(CreateTargetAssignment::class)
        ->fillForm([
            'cycle_id' => $cycle->id,
            'user_id' => $user->id,
            'basis' => 'tier',
            'target_tier_id' => null,
            'reason' => AssignmentReason::Initial->value,
            'effective_from' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['target_tier_id']);
});

test('an assignment can be created with a tier', function (): void {
    $cycle = Cycle::factory()->create();
    $user = User::factory()->withRole('sales_rep')->create();
    $tier = TargetTier::factory()->create(['active' => true]);

    livewire(CreateTargetAssignment::class)
        ->fillForm([
            'cycle_id' => $cycle->id,
            'user_id' => $user->id,
            'basis' => 'tier',
            'target_tier_id' => $tier->id,
            'reason' => AssignmentReason::Initial->value,
            'effective_from' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('target_assignments', [
        'cycle_id' => $cycle->id,
        'user_id' => $user->id,
        'target_tier_id' => $tier->id,
        'basis' => 'tier',
    ]);
});
