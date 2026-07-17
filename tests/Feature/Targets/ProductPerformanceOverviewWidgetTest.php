<?php

use App\Filament\Field\Resources\Products\Widgets\ProductPerformanceOverviewWidget;
use App\Models\Cycle;
use App\Models\Product;
use App\Models\RepMonthlyTarget;
use App\Models\User;

use function Pest\Livewire\livewire;

test('it shows target ytd and attainment when the rep has materialized monthly targets', function (): void {
    $cycle = Cycle::factory()->current()->create([
        'starts_on' => now()->subMonth()->startOfMonth(),
        'ends_on' => now()->addMonths(6)->endOfMonth(),
    ]);

    $product = Product::factory()->create();
    $rep = User::factory()->withRole('sales_rep')->create();

    RepMonthlyTarget::factory()->create([
        'cycle_id' => $cycle->id,
        'user_id' => $rep->id,
        'product_id' => $product->id,
        'year_month' => now()->startOfMonth(),
        'target_qty' => 100,
    ]);

    $this->actingAs($rep);

    livewire(ProductPerformanceOverviewWidget::class, ['record' => $product])
        ->assertSee('100.00')
        ->assertSee('0.0%')
        ->assertDontSee('—');
});

test('it falls back to placeholders when there is no current cycle', function (): void {
    $product = Product::factory()->create();
    $rep = User::factory()->withRole('sales_rep')->create();

    $this->actingAs($rep);

    livewire(ProductPerformanceOverviewWidget::class, ['record' => $product])
        ->assertSeeText('—');
});
