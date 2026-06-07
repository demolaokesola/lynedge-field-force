<?php

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\DepositAllocation;

test('a fresh deposit defaults to unreconciled', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);

    expect($deposit->status)->toBe(DepositStatus::Unreconciled);
});

test('a partial allocation transitions status to partially_reconciled', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 400]);

    expect($deposit->fresh()->status)->toBe(DepositStatus::PartiallyReconciled);
});

test('a full allocation transitions status to reconciled', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 1000]);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled);
});

test('two partial allocations summing to the full amount reconcile the deposit', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 600]);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 400]);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled);
});

test('deleting the only allocation reverts status to unreconciled', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);
    $allocation = DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 1000]);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled);

    $allocation->delete();

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled);
});

test('disputed status is preserved when allocations are added', function (): void {
    $deposit = Deposit::factory()->disputed()->create(['amount' => 1000]);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 1000]);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Disputed);
});

test('disputed status is preserved when allocations are deleted', function (): void {
    $deposit = Deposit::factory()->disputed()->create(['amount' => 1000]);
    $allocation = DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 500]);

    $allocation->delete();

    expect($deposit->fresh()->status)->toBe(DepositStatus::Disputed);
});

test('remaining balance is correct after partial allocation', function (): void {
    $deposit = Deposit::factory()->create(['amount' => '1000.00']);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => '300.00']);

    expect($deposit->fresh()->remainingBalance()->amount)->toBe('700.00');
});
