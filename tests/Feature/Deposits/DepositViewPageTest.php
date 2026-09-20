<?php

use App\Enums\DepositStatus;
use App\Filament\Shared\Resources\Deposits\DepositResource;
use App\Filament\Shared\Resources\Deposits\Pages\ViewDeposit;
use App\Models\Deposit;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

/**
 * The view page is a rep's only window into a deposit after submission: read-only,
 * scoped by Deposit::visibleTo, and the place the dispute reason is surfaced.
 */
beforeEach(function (): void {
    // The deposit edit form's "Received By" select lists users by role and needs the role to exist.
    Role::findOrCreate('sales_rep', 'web');
});

describe('as a sales rep in the field panel', function (): void {
    beforeEach(function (): void {
        Filament::setCurrentPanel(Filament::getPanel('field'));

        $this->rep = User::factory()->withRole('sales_rep')->create();
        $this->actingAs($this->rep);
    });

    test('a rep can open the read-only details of their own deposit', function (): void {
        $deposit = Deposit::factory()->by($this->rep)->create(['amount' => '1234567.50']);

        livewire(ViewDeposit::class, ['record' => $deposit->id])
            ->assertSuccessful()
            ->assertSee($deposit->customer->name)
            ->assertSee('1,234,567.50');
    });

    test('a rep sees the dispute reason on a disputed deposit', function (): void {
        $deposit = Deposit::factory()->by($this->rep)->create();
        $deposit->markDisputed('Teller slip amount differs from the bank statement');

        livewire(ViewDeposit::class, ['record' => $deposit->id])
            ->assertSuccessful()
            ->assertSee('Dispute reason')
            ->assertSee('Teller slip amount differs from the bank statement');
    });

    test('the dispute section is hidden when the deposit is not disputed', function (): void {
        $deposit = Deposit::factory()->by($this->rep)->create();

        livewire(ViewDeposit::class, ['record' => $deposit->id])
            ->assertSuccessful()
            ->assertDontSee('Dispute reason');
    });

    test('a rep sees no edit, reconciliation or dispute actions', function (): void {
        $deposit = Deposit::factory()->by($this->rep)->create();

        livewire(ViewDeposit::class, ['record' => $deposit->id])
            ->assertActionHidden('edit')
            ->assertActionHidden('reconcile')
            ->assertActionHidden('undoReconciliation')
            ->assertActionHidden('markDisputed')
            ->assertActionHidden('clearDispute');
    });

    test('a rep cannot view another rep\'s deposit', function (): void {
        $foreign = Deposit::factory()->create();

        $this->get(DepositResource::getUrl('view', ['record' => $foreign]))
            ->assertNotFound();
    });

    test('the deposits list links each row to its view page', function (): void {
        $deposit = Deposit::factory()->by($this->rep)->create();

        $this->get(DepositResource::getUrl('index'))
            ->assertOk()
            ->assertSee(DepositResource::getUrl('view', ['record' => $deposit]), false);
    });
});

describe('as an accountant in the office panel', function (): void {
    beforeEach(function (): void {
        Filament::setCurrentPanel(Filament::getPanel('office'));

        $this->actingAs(User::factory()->withRole('accountant')->create());
    });

    test('an accountant can dispute a deposit from its view page', function (): void {
        $deposit = Deposit::factory()->create();

        livewire(ViewDeposit::class, ['record' => $deposit->id])
            ->assertActionVisible('edit')
            ->assertActionVisible('reconcile')
            ->assertActionVisible('markDisputed')
            ->callAction('markDisputed', ['dispute_reason' => 'Customer denies making this deposit'])
            ->assertHasNoActionErrors()
            ->assertNotified('Deposit marked as disputed');

        expect($deposit->fresh()->status)->toBe(DepositStatus::Disputed)
            ->and($deposit->fresh()->dispute_reason)->toBe('Customer denies making this deposit');
    });
});
