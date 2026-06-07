<?php

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\DepositAllocation;

test('allocation exceeding deposit amount throws a DomainException', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);

    expect(fn () => DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 1001]))
        ->toThrow(DomainException::class, 'Allocation total would exceed the deposit amount.');
});

test('allocation exactly matching deposit amount succeeds and reconciles the deposit', function (): void {
    $deposit = Deposit::factory()->create(['amount' => '500.00']);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => '500.00']);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled);
});

test('cumulative allocations exceeding deposit amount throw a DomainException', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 700]);

    expect(fn () => DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 400]))
        ->toThrow(DomainException::class);
});

test('cumulative allocations staying within deposit amount succeed', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 500]);
    DepositAllocation::factory()->forDeposit($deposit)->create(['amount' => 500]);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled);
    expect(DepositAllocation::where('deposit_id', $deposit->id)->count())->toBe(2);
});

test('deposit amount is not stored as zero after creation', function (): void {
    $deposit = Deposit::factory()->create(['amount' => '2500.00']);

    expect($deposit->amount->amount)->toBe('2500.00');
});
