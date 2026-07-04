<?php

use App\Filament\Widgets\CompanyRollupWidget;
use App\Filament\Widgets\DistributionTrendWidget;
use App\Filament\Widgets\ReconciliationAgingWidget;
use App\Filament\Widgets\TopBottomRepsWidget;
use App\Filament\Widgets\UnreconciledDepositsWidget;
use App\Models\User;

use function Pest\Livewire\livewire;

/**
 * Office-panel widget canView() role guards.
 * Platform Admin sees admin widgets; Accountant sees finance widgets.
 */
describe('CompanyRollupWidget canView', function (): void {
    it('is visible to platform_admin', function (): void {
        $admin = User::factory()->withRole('platform_admin')->create();
        $this->actingAs($admin);
        expect(CompanyRollupWidget::canView())->toBeTrue();
    });

    it('is visible to superuser', function (): void {
        $su = User::factory()->withRole('superuser')->create();
        $this->actingAs($su);
        expect(CompanyRollupWidget::canView())->toBeTrue();
    });

    it('is hidden from accountant', function (): void {
        $accountant = User::factory()->withRole('accountant')->create();
        $this->actingAs($accountant);
        expect(CompanyRollupWidget::canView())->toBeFalse();
    });
});

describe('TopBottomRepsWidget canView', function (): void {
    it('is visible to platform_admin', function (): void {
        $admin = User::factory()->withRole('platform_admin')->create();
        $this->actingAs($admin);
        expect(TopBottomRepsWidget::canView())->toBeTrue();
    });

    it('is hidden from accountant', function (): void {
        $accountant = User::factory()->withRole('accountant')->create();
        $this->actingAs($accountant);
        expect(TopBottomRepsWidget::canView())->toBeFalse();
    });

    it('renders without error when there is no current cycle', function (): void {
        $admin = User::factory()->withRole('platform_admin')->create();
        $this->actingAs($admin);

        livewire(TopBottomRepsWidget::class)
            ->assertSuccessful();
    });
});

describe('DistributionTrendWidget canView', function (): void {
    it('is visible to platform_admin', function (): void {
        $admin = User::factory()->withRole('platform_admin')->create();
        $this->actingAs($admin);
        expect(DistributionTrendWidget::canView())->toBeTrue();
    });

    it('is hidden from accountant', function (): void {
        $accountant = User::factory()->withRole('accountant')->create();
        $this->actingAs($accountant);
        expect(DistributionTrendWidget::canView())->toBeFalse();
    });
});

describe('UnreconciledDepositsWidget canView', function (): void {
    it('is visible to accountant', function (): void {
        $accountant = User::factory()->withRole('accountant')->create();
        $this->actingAs($accountant);
        expect(UnreconciledDepositsWidget::canView())->toBeTrue();
    });

    it('is visible to superuser', function (): void {
        $su = User::factory()->withRole('superuser')->create();
        $this->actingAs($su);
        expect(UnreconciledDepositsWidget::canView())->toBeTrue();
    });

    it('is hidden from platform_admin', function (): void {
        $admin = User::factory()->withRole('platform_admin')->create();
        $this->actingAs($admin);
        expect(UnreconciledDepositsWidget::canView())->toBeFalse();
    });
});

describe('ReconciliationAgingWidget canView', function (): void {
    it('is visible to accountant', function (): void {
        $accountant = User::factory()->withRole('accountant')->create();
        $this->actingAs($accountant);
        expect(ReconciliationAgingWidget::canView())->toBeTrue();
    });

    it('is hidden from platform_admin', function (): void {
        $admin = User::factory()->withRole('platform_admin')->create();
        $this->actingAs($admin);
        expect(ReconciliationAgingWidget::canView())->toBeFalse();
    });
});
