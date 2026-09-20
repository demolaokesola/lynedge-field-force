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

test('an accountant can reconcile a deposit from the edit page with statement details', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $accountant = User::factory()->withRole('accountant')->create();
    $this->actingAs($accountant);

    $deposit = Deposit::factory()->create(['deposit_date' => '2026-09-10']);

    livewire(EditDeposit::class, ['record' => $deposit->id])
        ->assertActionVisible('reconcile')
        ->assertActionHidden('undoReconciliation')
        ->callAction('reconcile', [
            'statement_date' => '2026-09-12',
            'statement_reference' => 'STMT-0912-07',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Deposit reconciled');

    $deposit->refresh();

    expect($deposit->status)->toBe(DepositStatus::Reconciled)
        ->and($deposit->statement_date?->toDateString())->toBe('2026-09-12')
        ->and($deposit->statement_reference)->toBe('STMT-0912-07')
        ->and($deposit->reconciled_by_user_id)->toBe($accountant->id)
        ->and($deposit->reconciled_at)->not->toBeNull();
});

test('the reconcile action requires a statement date', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('accountant')->create());

    $deposit = Deposit::factory()->create();

    livewire(EditDeposit::class, ['record' => $deposit->id])
        ->callAction('reconcile', ['statement_date' => null])
        ->assertHasActionErrors(['statement_date' => 'required']);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled);
});

test('a platform admin can undo a reconciliation from the edit page', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $admin = User::factory()->withRole('platform_admin')->create();
    $this->actingAs($admin);

    $deposit = Deposit::factory()->create();
    $deposit->reconcile($admin, now(), 'STMT-1');

    livewire(EditDeposit::class, ['record' => $deposit->id])
        ->assertActionHidden('reconcile')
        ->assertActionVisible('undoReconciliation')
        ->callAction('undoReconciliation')
        ->assertNotified('Reconciliation undone');

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled)
        ->and($deposit->fresh()->reconciled_at)->toBeNull();
});

test('reconciliation actions are available as row actions on the deposits table', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
    $this->actingAs(User::factory()->withRole('accountant')->create());

    $deposit = Deposit::factory()->create();

    livewire(ListDeposits::class)
        ->callAction(TestAction::make('reconcile')->table($deposit), ['statement_date' => '2026-09-12'])
        ->assertHasNoActionErrors();

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled);

    livewire(ListDeposits::class)
        ->callAction(TestAction::make('undoReconciliation')->table($deposit));

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled);
});

test('a sales rep cannot see reconciliation actions on their own deposit', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $rep = User::factory()->withRole('sales_rep')->create();
    $this->actingAs($rep);

    $open = Deposit::factory()->by($rep)->create();
    $reconciled = Deposit::factory()->by($rep)->reconciled()->create();

    livewire(ListDeposits::class)
        ->assertCanSeeTableRecords([$open, $reconciled])
        ->assertActionHidden(TestAction::make('reconcile')->table($open))
        ->assertActionHidden(TestAction::make('undoReconciliation')->table($reconciled));
});
