<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Database\Factories\DepositAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reconciles a portion of a Deposit against an optional posted Distribution.
 * The sum of all allocations on a deposit must never exceed the deposit amount.
 * Deposit.status is refreshed automatically after each allocation change.
 */
#[Fillable(['deposit_id', 'distribution_id', 'amount', 'allocated_by_user_id', 'allocated_at'])]
class DepositAllocation extends Model
{
    /** @use HasFactory<DepositAllocationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (DepositAllocation $allocation): void {
            self::guardAmount($allocation);
        });

        static::updating(function (DepositAllocation $allocation): void {
            self::guardAmount($allocation);
        });

        static::saved(function (DepositAllocation $allocation): void {
            $allocation->deposit->refreshStatus();
        });

        static::deleted(function (DepositAllocation $allocation): void {
            $allocation->deposit->refreshStatus();
        });
    }

    /**
     * Enforce that the running total of allocations never exceeds the deposit amount.
     *
     * @throws \DomainException
     */
    private static function guardAmount(DepositAllocation $allocation): void
    {
        $existingSum = (string) $allocation->deposit->allocations()
            ->when($allocation->exists, fn ($q) => $q->whereKeyNot($allocation->id))
            ->sum('amount');

        $newAmount = (string) $allocation->amount;
        $depositAmount = (string) $allocation->deposit->amount;

        if (bccomp(bcadd($existingSum, $newAmount, 2), $depositAmount, 2) > 0) {
            throw new \DomainException('Allocation total would exceed the deposit amount.');
        }
    }

    /**
     * @return BelongsTo<Deposit, $this>
     */
    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    /**
     * @return BelongsTo<Distribution, $this>
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'allocated_at' => 'datetime',
        ];
    }
}
