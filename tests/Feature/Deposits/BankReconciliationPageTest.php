<?php

use App\Enums\DepositStatus;
use App\Filament\Office\Pages\BankReconciliation;
use App\Filament\Shared\Resources\Deposits\DepositResource;
use App\Models\BankAccount;
use App\Models\Deposit;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
});

describe('access', function (): void {
    it('is open to accountant and superuser', function (string $role): void {
        $this->actingAs(User::factory()->withRole($role)->create());

        expect(BankReconciliation::canAccess())->toBeTrue();

        $this->get(BankReconciliation::getUrl())->assertOk();
    })->with(['accountant', 'superuser']);

    it('is closed to platform_admin', function (): void {
        $this->actingAs(User::factory()->withRole('platform_admin')->create());

        expect(BankReconciliation::canAccess())->toBeFalse();

        $this->get(BankReconciliation::getUrl())->assertForbidden();
    });

    it('sits in the Finance navigation group', function (): void {
        expect(BankReconciliation::getNavigationGroup())->toBe('Finance');
    });
});

describe('queue', function (): void {
    beforeEach(function (): void {
        $this->actingAs(User::factory()->withRole('accountant')->create());
    });

    it('lists only unreconciled deposits', function (): void {
        $open = Deposit::factory()->create();
        $reconciled = Deposit::factory()->reconciled()->create();
        $disputed = Deposit::factory()->disputed()->create();

        livewire(BankReconciliation::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$reconciled, $disputed]);
    });

    it('narrows the queue by bank account', function (): void {
        $gtb = BankAccount::factory()->create();
        $zenith = BankAccount::factory()->create();
        $onGtb = Deposit::factory()->forBankAccount($gtb)->create();
        $onZenith = Deposit::factory()->forBankAccount($zenith)->create();

        livewire(BankReconciliation::class)
            ->assertCanSeeTableRecords([$onGtb, $onZenith])
            ->filterTable('bank_account_id', $gtb->id)
            ->assertCanSeeTableRecords([$onGtb])
            ->assertCanNotSeeTableRecords([$onZenith]);
    });

    it('links each row to the deposit edit page', function (): void {
        $deposit = Deposit::factory()->create();

        livewire(BankReconciliation::class)
            ->assertActionHasUrl(
                TestAction::make('open')->table($deposit),
                DepositResource::getUrl('edit', ['record' => $deposit]),
            );
    });

    it('reconciles a deposit from the queue and drops it from the list', function (): void {
        $deposit = Deposit::factory()->create();

        livewire(BankReconciliation::class)
            ->assertCanSeeTableRecords([$deposit])
            ->callAction(TestAction::make('reconcile')->table($deposit), [
                'statement_date' => '2026-09-15',
                'statement_reference' => 'STMT-0915-03',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Deposit reconciled')
            ->assertCanNotSeeTableRecords([$deposit]);

        $deposit->refresh();

        expect($deposit->status)->toBe(DepositStatus::Reconciled)
            ->and($deposit->statement_reference)->toBe('STMT-0915-03')
            ->and($deposit->reconciled_by_user_id)->toBe(auth()->id());
    });
});
