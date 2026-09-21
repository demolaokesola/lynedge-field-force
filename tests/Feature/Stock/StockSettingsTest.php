<?php

use App\Filament\Office\Pages\StockSettings as StockSettingsPage;
use App\Models\Setting;
use App\Models\User;
use App\Services\StockSettings;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The two Office-managed stock switches. Both default to off so a fresh install can
 * enter opening balances before any distribution touches the ledger.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
});

test('both switches default to off when no setting row exists', function (): void {
    $settings = app(StockSettings::class);

    expect(Setting::count())->toBe(0)
        ->and($settings->consumptionEnabled())->toBeFalse()
        ->and($settings->blockNegativeStock())->toBeFalse();
});

test('setting a switch persists it and busts the cache', function (): void {
    $settings = app(StockSettings::class);

    expect($settings->consumptionEnabled())->toBeFalse();

    $settings->setConsumptionEnabled(true);

    expect($settings->consumptionEnabled())->toBeTrue()
        ->and(Setting::query()->where('key', StockSettings::CONSUMPTION_ENABLED)->value('value'))->toBeTrue();

    $settings->setConsumptionEnabled(false);

    expect($settings->consumptionEnabled())->toBeFalse();
});

test('a platform_admin can save both switches from the Stock Settings page', function (): void {
    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    livewire(StockSettingsPage::class)
        ->assertSchemaStateSet([
            'consumption_enabled' => false,
            'block_negative_stock' => false,
        ])
        ->fillForm([
            'consumption_enabled' => true,
            'block_negative_stock' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings = app(StockSettings::class);

    expect($settings->consumptionEnabled())->toBeTrue()
        ->and($settings->blockNegativeStock())->toBeTrue();
});

test('the Stock Settings page reloads the saved values', function (): void {
    app(StockSettings::class)->setBlockNegativeStock(true);

    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    livewire(StockSettingsPage::class)
        ->assertSchemaStateSet([
            'consumption_enabled' => false,
            'block_negative_stock' => true,
        ]);
});

test('only platform_admin and superuser may open the Stock Settings page', function (string $role, bool $allowed): void {
    $this->actingAs(User::factory()->withRole($role)->create());

    expect(StockSettingsPage::canAccess())->toBe($allowed);
})->with([
    'platform_admin' => ['platform_admin', true],
    'superuser' => ['superuser', true],
    'accountant' => ['accountant', false],
    'hq_lead' => ['hq_lead', false],
    'sales_rep' => ['sales_rep', false],
]);
