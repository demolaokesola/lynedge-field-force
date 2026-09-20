<?php

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Model-level rules for one-to-one reconciliation: a deposit is matched to a
 * single bank-statement entry or it is not; there is no partial state.
 */
test('a fresh deposit defaults to unreconciled with no statement match', function (): void {
    $deposit = Deposit::factory()->create(['amount' => 1000]);

    expect($deposit->status)->toBe(DepositStatus::Unreconciled)
        ->and($deposit->reconciled_at)->toBeNull()
        ->and($deposit->reconciled_by_user_id)->toBeNull()
        ->and($deposit->statement_date)->toBeNull()
        ->and($deposit->statement_reference)->toBeNull();
});

test('reconciling records the statement match and who did it', function (): void {
    Carbon::setTestNow('2026-09-20 10:00:00');
    $accountant = User::factory()->withRole('accountant')->create();
    $deposit = Deposit::factory()->create(['amount' => 1000]);

    $deposit->reconcile($accountant, Carbon::parse('2026-09-18'), 'STMT-0918-01');

    $deposit->refresh();

    expect($deposit->status)->toBe(DepositStatus::Reconciled)
        ->and($deposit->reconciled_at?->toDateTimeString())->toBe('2026-09-20 10:00:00')
        ->and($deposit->reconciled_by_user_id)->toBe($accountant->id)
        ->and($deposit->reconciledBy->is($accountant))->toBeTrue()
        ->and($deposit->statement_date?->toDateString())->toBe('2026-09-18')
        ->and($deposit->statement_reference)->toBe('STMT-0918-01');

    Carbon::setTestNow();
});

test('the statement reference is optional', function (): void {
    $accountant = User::factory()->withRole('accountant')->create();
    $deposit = Deposit::factory()->create();

    $deposit->reconcile($accountant, now());

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled)
        ->and($deposit->fresh()->statement_reference)->toBeNull();
});

test('only an unreconciled deposit can be reconciled', function (Deposit $deposit): void {
    $accountant = User::factory()->withRole('accountant')->create();

    expect(fn () => $deposit->reconcile($accountant, now()))
        ->toThrow(DomainException::class, 'Only an unreconciled deposit can be reconciled.');

    expect($deposit->fresh()->status)->not->toBe(DepositStatus::Unreconciled);
})->with([
    'already reconciled' => fn () => Deposit::factory()->reconciled()->create(),
    'disputed' => fn () => Deposit::factory()->disputed()->create(),
]);

test('undoing a reconciliation clears the statement match', function (): void {
    $accountant = User::factory()->withRole('accountant')->create();
    $deposit = Deposit::factory()->create();
    $deposit->reconcile($accountant, now(), 'STMT-1');

    $deposit->fresh()->unreconcile();

    $deposit->refresh();

    expect($deposit->status)->toBe(DepositStatus::Unreconciled)
        ->and($deposit->reconciled_at)->toBeNull()
        ->and($deposit->reconciled_by_user_id)->toBeNull()
        ->and($deposit->statement_date)->toBeNull()
        ->and($deposit->statement_reference)->toBeNull();
});

test('only a reconciled deposit can be unreconciled', function (Deposit $deposit): void {
    expect(fn () => $deposit->unreconcile())
        ->toThrow(DomainException::class, 'Only a reconciled deposit can be unreconciled.');
})->with([
    'unreconciled' => fn () => Deposit::factory()->create(),
    'disputed' => fn () => Deposit::factory()->disputed()->create(),
]);

test('a reconciled deposit cannot be disputed until the reconciliation is undone', function (): void {
    $accountant = User::factory()->withRole('accountant')->create();
    $deposit = Deposit::factory()->create();
    $deposit->reconcile($accountant, now(), 'STMT-1');

    expect(fn () => $deposit->markDisputed('Amount does not match statement'))
        ->toThrow(DomainException::class);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Reconciled)
        ->and($deposit->fresh()->dispute_reason)->toBeNull();

    $deposit->fresh()->unreconcile();
    $deposit->fresh()->markDisputed('Amount does not match statement');

    expect($deposit->fresh()->status)->toBe(DepositStatus::Disputed);
});

test('clearing a dispute on a non-disputed deposit is rejected', function (Deposit $deposit): void {
    expect(fn () => $deposit->clearDispute())->toThrow(DomainException::class);
})->with([
    'unreconciled' => fn () => Deposit::factory()->create(),
    'reconciled' => function () {
        $deposit = Deposit::factory()->create();
        $deposit->reconcile(User::factory()->withRole('accountant')->create(), now());

        return $deposit;
    },
]);

test('clearing a dispute returns the deposit to unreconciled and discards the reason', function (): void {
    $deposit = Deposit::factory()->disputed()->create();

    expect($deposit->dispute_reason)->not->toBeNull();

    $deposit->clearDispute();

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled)
        ->and($deposit->fresh()->dispute_reason)->toBeNull();
});

test('marking a deposit as disputed stores the trimmed reason', function (): void {
    $deposit = Deposit::factory()->create();

    $deposit->markDisputed("  Amount does not match statement \n");

    expect($deposit->fresh()->status)->toBe(DepositStatus::Disputed)
        ->and($deposit->fresh()->dispute_reason)->toBe('Amount does not match statement');
});

test('marking a deposit as disputed without a reason is rejected', function (string $reason): void {
    $deposit = Deposit::factory()->create();

    expect(fn () => $deposit->markDisputed($reason))->toThrow(DomainException::class);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Unreconciled)
        ->and($deposit->fresh()->dispute_reason)->toBeNull();
})->with([
    'empty' => [''],
    'whitespace' => ['   '],
]);

test('the unreconciled scope excludes reconciled and disputed deposits', function (): void {
    $open = Deposit::factory()->create();
    Deposit::factory()->reconciled()->create();
    Deposit::factory()->disputed()->create();

    expect(Deposit::query()->unreconciled()->pluck('id')->all())->toBe([$open->id]);
});

test('deposit amount is not stored as zero after creation', function (): void {
    $deposit = Deposit::factory()->create(['amount' => '2500.00']);

    expect($deposit->amount->amount)->toBe('2500.00');
});
