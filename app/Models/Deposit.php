<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\DepositChannel;
use App\Enums\DepositStatus;
use App\Models\Concerns\ScopesToViewer;
use App\Support\Money;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer deposit collected by a rep in the field.
 * territory_id is denormalised from the customer at write time.
 *
 * Never mass-assign: user_id, territory_id, status.
 */
#[Fillable(['customer_id', 'amount', 'deposit_date', 'reference', 'bank', 'channel', 'notes'])]
class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory, ScopesToViewer;

    protected static function booted(): void
    {
        static::creating(function (Deposit $deposit): void {
            $deposit->user_id ??= auth()->id();
            $deposit->territory_id = $deposit->customer->territory_id;
            $deposit->status ??= DepositStatus::Unreconciled;
        });
    }

    /**
     * Sum of all allocations against this deposit.
     */
    public function allocatedAmount(): Money
    {
        $sum = $this->allocations()->sum('amount');

        return Money::of((string) $sum);
    }

    /**
     * Unallocated balance remaining on this deposit.
     */
    public function remainingBalance(): Money
    {
        return $this->amount->minus($this->allocatedAmount());
    }

    /**
     * Recompute status from allocated total. Disputed status is a manual override
     * that survives automatic recalculation.
     */
    public function refreshStatus(): void
    {
        if ($this->status === DepositStatus::Disputed) {
            return;
        }

        $allocated = $this->allocatedAmount();
        $total = $this->amount;

        $this->status = match (true) {
            $allocated->isZero() => DepositStatus::Unreconciled,
            bccomp($allocated->amount, $total->amount, 2) >= 0 => DepositStatus::Reconciled,
            default => DepositStatus::PartiallyReconciled,
        };

        $this->saveQuietly();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * @return HasMany<DepositAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(DepositAllocation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'deposit_date' => 'date',
            'status' => DepositStatus::class,
            'channel' => DepositChannel::class,
        ];
    }
}
