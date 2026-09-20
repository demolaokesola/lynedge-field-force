<?php

use App\Enums\DepositChannel;
use App\Filament\Shared\Resources\Deposits\Pages\ListDeposits;
use App\Models\BankAccount;
use App\Models\Deposit;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('office'));

    $this->actingAs(User::factory()->withRole('accountant')->create());
});

test('deposits can be filtered by bank account', function (): void {
    $gtb = BankAccount::factory()->create();
    $zenith = BankAccount::factory()->create();
    $onGtb = Deposit::factory()->forBankAccount($gtb)->create();
    $onZenith = Deposit::factory()->forBankAccount($zenith)->create();

    livewire(ListDeposits::class)
        ->assertCanSeeTableRecords([$onGtb, $onZenith])
        ->filterTable('bank_account_id', $gtb->id)
        ->assertCanSeeTableRecords([$onGtb])
        ->assertCanNotSeeTableRecords([$onZenith]);
});

test('deposits can be filtered by channel', function (): void {
    $cash = Deposit::factory()->create(['channel' => DepositChannel::Cash]);
    $transfer = Deposit::factory()->create(['channel' => DepositChannel::BankTransfer]);

    livewire(ListDeposits::class)
        ->filterTable('channel', DepositChannel::BankTransfer->value)
        ->assertCanSeeTableRecords([$transfer])
        ->assertCanNotSeeTableRecords([$cash]);
});

test('deposits can be filtered by territory', function (): void {
    $inside = Deposit::factory()->create();
    $outside = Deposit::factory()->create();

    livewire(ListDeposits::class)
        ->filterTable('territory_id', $inside->territory_id)
        ->assertCanSeeTableRecords([$inside])
        ->assertCanNotSeeTableRecords([$outside]);
});

test('deposits can be filtered by a deposit date range', function (): void {
    $january = Deposit::factory()->create(['deposit_date' => '2026-01-15']);
    $march = Deposit::factory()->create(['deposit_date' => '2026-03-15']);
    $june = Deposit::factory()->create(['deposit_date' => '2026-06-15']);

    livewire(ListDeposits::class)
        ->filterTable('deposit_date', ['from' => '2026-02-01', 'until' => '2026-04-30'])
        ->assertCanSeeTableRecords([$march])
        ->assertCanNotSeeTableRecords([$january, $june]);
});
