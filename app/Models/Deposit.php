<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\DepositChannel;
use App\Enums\DepositStatus;
use App\Models\Concerns\ScopesToViewer;
use Carbon\CarbonInterface;
use Database\Factories\DepositFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer deposit collected by a rep in the field.
 * territory_id is denormalised from the customer at write time.
 *
 * Reconciliation is one-to-one: an accountant matches the deposit to a single
 * bank-statement amount via {@see reconcile()}, which records the statement
 * details on the row. There is no partial reconciliation.
 *
 * Never mass-assign: user_id, territory_id, status, dispute_reason, or the reconciliation columns.
 */
#[Fillable(['customer_id', 'bank_account_id', 'amount', 'deposit_date', 'reference', 'channel', 'notes'])]
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
     * Match this deposit to a bank-statement entry. Only an unreconciled deposit can
     * be reconciled; disputed deposits must have the dispute cleared first.
     *
     * @throws DomainException
     */
    public function reconcile(User $reconciledBy, CarbonInterface $statementDate, ?string $statementReference = null): void
    {
        if ($this->status !== DepositStatus::Unreconciled) {
            throw new DomainException('Only an unreconciled deposit can be reconciled.');
        }

        $this->status = DepositStatus::Reconciled;
        $this->reconciled_at = now();
        $this->reconciled_by_user_id = $reconciledBy->id;
        $this->statement_date = $statementDate;
        $this->statement_reference = $statementReference;
        $this->saveQuietly();
    }

    /**
     * Undo a statement match and return the deposit to the unreconciled queue.
     *
     * @throws DomainException
     */
    public function unreconcile(): void
    {
        if ($this->status !== DepositStatus::Reconciled) {
            throw new DomainException('Only a reconciled deposit can be unreconciled.');
        }

        $this->status = DepositStatus::Unreconciled;
        $this->reconciled_at = null;
        $this->reconciled_by_user_id = null;
        $this->statement_date = null;
        $this->statement_reference = null;
        $this->saveQuietly();
    }

    /**
     * Manual override: flag an unreconciled deposit as disputed with a reason the rep
     * can read. The status is frozen until {@see clearDispute()} is called. A reconciled
     * deposit is already matched to the statement and must be unreconciled first.
     *
     * @throws DomainException
     */
    public function markDisputed(string $reason): void
    {
        if ($this->status !== DepositStatus::Unreconciled) {
            throw new DomainException('Only an unreconciled deposit can be disputed.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('A reason is required to dispute a deposit.');
        }

        $this->status = DepositStatus::Disputed;
        $this->dispute_reason = $reason;
        $this->saveQuietly();
    }

    /**
     * Lift a dispute and return the deposit to the unreconciled queue. The reason is discarded.
     *
     * @throws DomainException
     */
    public function clearDispute(): void
    {
        if ($this->status !== DepositStatus::Disputed) {
            throw new DomainException('Only a disputed deposit can have its dispute cleared.');
        }

        $this->status = DepositStatus::Unreconciled;
        $this->dispute_reason = null;
        $this->saveQuietly();
    }

    /**
     * @param  Builder<Deposit>  $query
     */
    public function scopeUnreconciled(Builder $query): void
    {
        $query->where('status', DepositStatus::Unreconciled);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * @return BelongsTo<Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'deposit_date' => 'date',
            'reconciled_at' => 'datetime',
            'statement_date' => 'date',
            'status' => DepositStatus::class,
            'channel' => DepositChannel::class,
        ];
    }
}
