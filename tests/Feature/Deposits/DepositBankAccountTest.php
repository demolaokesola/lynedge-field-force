<?php

use App\Filament\Exports\UnreconciledDepositsExporter;
use App\Filament\Shared\Resources\Deposits\Pages\CreateDeposit;
use App\Filament\Shared\Resources\Deposits\Pages\EditDeposit;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));

    // The form's "Received By" select lists users by role and needs the role to exist.
    Role::findOrCreate('sales_rep', 'web');

    $this->actingAs(User::factory()->withRole('accountant')->create());
});

test('a deposit is created against a selected bank account', function (): void {
    $customer = Customer::factory()->create();
    $account = BankAccount::factory()->create();

    livewire(CreateDeposit::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'bank_account_id' => $account->id,
            'amount' => 25000,
            'deposit_date' => today()->toDateString(),
            'reference' => 'REF-001',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $deposit = Deposit::firstWhere('reference', 'REF-001');

    expect($deposit)->not->toBeNull()
        ->and($deposit->bank_account_id)->toBe($account->id)
        ->and($deposit->bankAccount->is($account))->toBeTrue();
});

test('a deposit requires a bank account', function (): void {
    $customer = Customer::factory()->create();

    livewire(CreateDeposit::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'bank_account_id' => null,
            'amount' => 25000,
            'deposit_date' => today()->toDateString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['bank_account_id' => 'required']);

    expect(Deposit::count())->toBe(0);
});

test('editing a deposit on a deactivated bank account still hydrates and saves', function (): void {
    $inactive = BankAccount::factory()->inactive()->create();
    $rep = User::factory()->withRole('sales_rep')->create();
    $deposit = Deposit::factory()->forBankAccount($inactive)->by($rep)->create();

    livewire(EditDeposit::class, ['record' => $deposit->id])
        ->assertSuccessful()
        ->assertFormSet(['bank_account_id' => $inactive->id])
        ->fillForm(['reference' => 'UPDATED'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($deposit->fresh()->reference)->toBe('UPDATED')
        ->and($deposit->fresh()->bank_account_id)->toBe($inactive->id);
});

test('the unreconciled deposits export includes the bank account columns', function (): void {
    $names = collect(UnreconciledDepositsExporter::getColumns())
        ->map(fn (ExportColumn $column): string => $column->getName())
        ->all();

    expect($names)->toContain('bankAccount.bank_name', 'bankAccount.account_number')
        ->and($names)->not->toContain('bank');
});
