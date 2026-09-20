<?php

namespace App\Filament\Shared\Resources\Deposits\Schemas;

use App\Enums\DepositChannel;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use App\Services\RepScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DepositForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(fn (): array => static::customerOptions())
                    ->required()
                    ->searchable()
                    ->native(false),
                TextInput::make('amount')
                    ->label('Amount (₦)')
                    ->numeric()
                    ->minValue(0.01)
                    ->required(),
                DatePicker::make('deposit_date')
                    ->required()
                    ->default(today())
                    ->native(false),
                TextInput::make('reference')
                    ->maxLength(255),
                Select::make('bank_account_id')
                    ->label('Bank Account')
                    ->options(fn (?Deposit $record): array => static::bankAccountOptions($record))
                    ->required()
                    ->searchable()
                    ->native(false),
                Select::make('channel')
                    ->options(DepositChannel::class)
                    ->native(false),
                // Accountants recording on behalf of a rep may override the collector.
                Select::make('user_id')
                    ->label('Received By')
                    ->options(fn (): array => static::repOptions())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['accountant', 'platform_admin']) ?? false)
                    ->helperText('Leave blank to default to your own account.'),
                Textarea::make('notes')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Customers visible to the acting user, scoped to their territories when they are a rep.
     *
     * @return array<int, string>
     */
    public static function customerOptions(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $query = Customer::query()->orderBy('name');

        if ($user->hasRole('sales_rep')) {
            $repScope = app(RepScope::class);
            $territoryIds = $repScope->activePositions($user)->pluck('territory_id')
                ->merge($repScope->positionsSupervisedBy($user)->pluck('territory_id'))
                ->unique();
            $query->whereIn('territory_id', $territoryIds);
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * Active company bank accounts, plus the account already linked to the record being
     * edited so a deposit on a since-deactivated account still hydrates and saves.
     *
     * @return array<int, string>
     */
    public static function bankAccountOptions(?Deposit $record = null): array
    {
        $currentId = $record?->bank_account_id;

        return BankAccount::query()
            ->where(function (Builder $query) use ($currentId): void {
                $query->active();

                if ($currentId !== null) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get()
            ->mapWithKeys(fn (BankAccount $account): array => [$account->id => $account->label])
            ->all();
    }

    /**
     * All field-role users for the "Received By" override available to accountants/admins.
     *
     * @return array<int, string>
     */
    public static function repOptions(): array
    {
        return User::role('sales_rep')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
