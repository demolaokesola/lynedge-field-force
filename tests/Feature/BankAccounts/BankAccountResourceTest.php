<?php

use App\Filament\Office\Resources\BankAccounts\Pages\CreateBankAccount;
use App\Filament\Office\Resources\BankAccounts\Pages\EditBankAccount;
use App\Filament\Office\Resources\BankAccounts\Pages\ListBankAccounts;
use App\Filament\Shared\Resources\Deposits\Schemas\DepositForm;
use App\Models\BankAccount;
use App\Models\Deposit;
use App\Models\User;
use App\Policies\BankAccountPolicy;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));
});

test('platform_admin and accountant can list, create and edit bank accounts', function (string $role): void {
    $this->actingAs(User::factory()->withRole($role)->create());

    $existing = BankAccount::factory()->create();

    livewire(ListBankAccounts::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$existing]);

    livewire(CreateBankAccount::class)
        ->fillForm([
            'bank_name' => 'GTBank',
            'account_name' => 'Lynedge Pharma Ltd',
            'account_number' => '0123456789',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = BankAccount::firstWhere('account_number', '0123456789');

    expect($created)->not->toBeNull()
        ->and($created->bank_name)->toBe('GTBank')
        ->and($created->is_active)->toBeTrue();

    livewire(EditBankAccount::class, ['record' => $created->id])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($created->fresh()->is_active)->toBeFalse();
})->with([
    'platform_admin' => ['platform_admin'],
    'accountant' => ['accountant'],
]);

test('non-finance roles cannot view bank accounts', function (string $role): void {
    $user = User::factory()->withRole($role)->create();
    $policy = new BankAccountPolicy;

    expect($policy->viewAny($user))->toBeFalse()
        ->and($policy->create($user))->toBeFalse();
})->with([
    'sales_rep' => ['sales_rep'],
    'hq_lead' => ['hq_lead'],
    'regional_head' => ['regional_head'],
]);

test('the same account number cannot be added twice for the same bank', function (): void {
    $this->actingAs(User::factory()->withRole('accountant')->create());

    BankAccount::factory()->create(['bank_name' => 'Zenith Bank', 'account_number' => '1111111111']);

    livewire(CreateBankAccount::class)
        ->fillForm([
            'bank_name' => 'Zenith Bank',
            'account_name' => 'Duplicate',
            'account_number' => '1111111111',
        ])
        ->call('create')
        ->assertHasFormErrors(['account_number' => 'unique']);

    expect(BankAccount::count())->toBe(1);
});

test('the same account number is allowed at a different bank', function (): void {
    $this->actingAs(User::factory()->withRole('accountant')->create());

    BankAccount::factory()->create(['bank_name' => 'Zenith Bank', 'account_number' => '1111111111']);

    livewire(CreateBankAccount::class)
        ->fillForm([
            'bank_name' => 'Access Bank',
            'account_name' => 'Other Bank Same Number',
            'account_number' => '1111111111',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(BankAccount::count())->toBe(2);
});

test('the account number must be ten digits', function (): void {
    $this->actingAs(User::factory()->withRole('accountant')->create());

    livewire(CreateBankAccount::class)
        ->fillForm([
            'bank_name' => 'UBA',
            'account_name' => 'Short',
            'account_number' => '12345',
        ])
        ->call('create')
        ->assertHasFormErrors(['account_number']);
});

test('deactivated accounts are hidden from the deposit form options', function (): void {
    $active = BankAccount::factory()->create();
    $inactive = BankAccount::factory()->inactive()->create();

    $options = DepositForm::bankAccountOptions();

    expect($options)->toHaveKey($active->id)
        ->and($options)->not->toHaveKey($inactive->id)
        ->and($options[$active->id])->toBe($active->label);
});

test('a bank account with deposits cannot be deleted but an unused one can', function (): void {
    $accountant = User::factory()->withRole('accountant')->create();
    $used = BankAccount::factory()->create();
    $unused = BankAccount::factory()->create();
    Deposit::factory()->forBankAccount($used)->create();

    $policy = new BankAccountPolicy;

    expect($policy->delete($accountant, $used))->toBeFalse()
        ->and($policy->delete($accountant, $unused))->toBeTrue();
});
