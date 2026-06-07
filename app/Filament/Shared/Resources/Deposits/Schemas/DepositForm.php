<?php

namespace App\Filament\Shared\Resources\Deposits\Schemas;

use App\Enums\DepositChannel;
use App\Models\Customer;
use App\Models\User;
use App\Services\RepScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                TextInput::make('bank')
                    ->maxLength(255),
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

        if ($user->hasAnyRole(['sales_rep', 'supervisor'])) {
            $territoryIds = app(RepScope::class)->activePositions($user)->pluck('territory_id');
            $query->whereIn('territory_id', $territoryIds);
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * All field-role users for the "Received By" override available to accountants/admins.
     *
     * @return array<int, string>
     */
    public static function repOptions(): array
    {
        return User::role(['sales_rep', 'supervisor'])
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
