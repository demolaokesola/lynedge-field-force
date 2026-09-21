<?php

use App\Filament\Field\Widgets\LowStockWidget;
use App\Filament\Office\Pages\StockSettings as StockSettingsPage;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionProductStock;
use App\Models\Product;
use App\Models\Territory;
use App\Models\User;
use App\Services\StockSettings;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The rep's low-stock alert: balances at or below the Office threshold for the
 * positions they hold or supervise. A threshold of zero switches it off.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));

    $this->territory = Territory::factory()->liberal()->create();
    $this->position = Position::factory()->create(['territory_id' => $this->territory->id]);
    $this->otherPosition = Position::factory()->create(['territory_id' => $this->territory->id]);

    $this->rep = User::factory()->withRole('sales_rep')->create();
    PositionAssignment::factory()->create(['position_id' => $this->position->id, 'user_id' => $this->rep->id, 'effective_to' => null]);

    $this->low = PositionProductStock::factory()->forPosition($this->position)->create(['product_id' => Product::factory()->create(['name' => 'Low Product'])->id, 'quantity' => 4]);
    $this->atThreshold = PositionProductStock::factory()->forPosition($this->position)->create(['product_id' => Product::factory()->create(['name' => 'Edge Product'])->id, 'quantity' => 10]);
    $this->fine = PositionProductStock::factory()->forPosition($this->position)->create(['product_id' => Product::factory()->create(['name' => 'Fine Product'])->id, 'quantity' => 11]);
    $this->foreignLow = PositionProductStock::factory()->forPosition($this->otherPosition)->create(['product_id' => Product::factory()->create(['name' => 'Foreign Product'])->id, 'quantity' => 1]);
});

test('the threshold defaults to 10 and can be changed from Stock Settings', function (): void {
    expect(app(StockSettings::class)->lowStockThreshold())->toBe(10);

    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    livewire(StockSettingsPage::class)
        ->assertSchemaStateSet(['low_stock_threshold' => 10])
        ->fillForm(['low_stock_threshold' => 25])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(app(StockSettings::class)->lowStockThreshold())->toBe(25);
});

test('a negative threshold is rejected by the form', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    livewire(StockSettingsPage::class)
        ->fillForm(['low_stock_threshold' => -1])
        ->call('save')
        ->assertHasFormErrors(['low_stock_threshold']);
});

test('the widget lists balances at or below the threshold for the rep\'s own positions only', function (): void {
    $this->actingAs($this->rep);

    expect(LowStockWidget::canView())->toBeTrue();

    livewire(LowStockWidget::class)
        ->assertCanSeeTableRecords([$this->low, $this->atThreshold])
        ->assertCanNotSeeTableRecords([$this->fine, $this->foreignLow])
        ->assertSee('My Low Stock (≤ 10)');
});

test('the widget is hidden when the threshold is zero, and for non-rep roles', function (): void {
    app(StockSettings::class)->setLowStockThreshold(0);
    $this->actingAs($this->rep);

    expect(LowStockWidget::canView())->toBeFalse();

    app(StockSettings::class)->setLowStockThreshold(10);
    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    expect(LowStockWidget::canView())->toBeFalse();
});
