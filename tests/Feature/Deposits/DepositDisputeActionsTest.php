<?php

use App\Enums\DepositStatus;
use App\Filament\Shared\Resources\Deposits\Pages\EditDeposit;
use App\Filament\Shared\Resources\Deposits\Pages\ListDeposits;
use App\Models\Deposit;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    // The deposit edit form's "Received By" select lists users by role and needs the role to exist.
    Role::findOrCreate('sales_rep', 'web');
});

test('an accountant can mark a deposit as disputed and it cannot then be reconciled', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $accountant = User::factory()->withRole('accountant')->create();
    $this->actingAs($accountant);

    $deposit = Deposit::factory()->create(['amount' => 1000]);

    livewire(EditDeposit::class, ['record' => $deposit->id])
        ->assertActionVisible('markDisputed')
        ->assertActionHidden('clearDispute')
        ->callAction('markDisputed', ['dispute_reason' => 'Teller slip does not match the bank statement'])
        ->assertHasNoActionErrors()
        ->assertNotified('Deposit marked as disputed');

    expect($deposit->fresh()->status)->toBe(DepositStatus::Disputed)
        ->and($deposit->fresh()->dispute_reason)->toBe('Teller slip does not match the bank statement');

    expect(fn () => $deposit->fresh()->reconcile($accountant, now()))
        ->toThrow(DomainException::class);
});

test('marking a deposit as disputed requires a reason', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('accountant')->create());

    $deposit = Deposit::factory()->create(['amount' => 1000]);

    livewire(EditDeposit::class, ['record' => $deposit->id])
        ->callAction('markDisputed', ['dispute_reason' => ''])
        ->assertHasActionErrors(['dispute_reason' => 'required'])
        ->assertNotNotified();

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled)
        ->and($deposit->fresh()->dispute_reason)->toBeNull();
});

test('a reconciled deposit cannot be marked as disputed', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $admin = User::factory()->withRole('platform_admin')->create();
    $this->actingAs($admin);

    $deposit = Deposit::factory()->create(['amount' => 1000]);
    $deposit->reconcile($admin, now(), 'STMT-1');

    livewire(EditDeposit::class, ['record' => $deposit->id])
        ->assertActionHidden('markDisputed')
        ->assertActionHidden('clearDispute')
        ->assertActionVisible('undoReconciliation');

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled);
});

test('clearing a dispute returns the deposit to unreconciled', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('platform_admin')->create());

    $deposit = Deposit::factory()->disputed()->create(['amount' => 1000]);

    livewire(EditDeposit::class, ['record' => $deposit->id])
        ->assertActionHidden('markDisputed')
        ->assertActionVisible('clearDispute')
        ->callAction('clearDispute')
        ->assertNotified('Dispute cleared');

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled)
        ->and($deposit->fresh()->dispute_reason)->toBeNull();
});

test('dispute actions are available as row actions on the deposits table', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('accountant')->create());

    $deposit = Deposit::factory()->create(['amount' => 1000]);

    livewire(ListDeposits::class)
        ->callAction(TestAction::make('markDisputed')->table($deposit), ['dispute_reason' => 'Customer denies making this deposit']);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Disputed)
        ->and($deposit->fresh()->dispute_reason)->toBe('Customer denies making this deposit');
});

test('a sales rep cannot see or call dispute actions on their own deposit', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $rep = User::factory()->withRole('sales_rep')->create();
    $this->actingAs($rep);

    $deposit = Deposit::factory()->by($rep)->create(['amount' => 1000]);

    livewire(ListDeposits::class)
        ->assertCanSeeTableRecords([$deposit])
        ->assertActionHidden(TestAction::make('markDisputed')->table($deposit))
        ->assertActionHidden(TestAction::make('clearDispute')->table($deposit));

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled);
});
