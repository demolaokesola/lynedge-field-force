<?php

namespace App\Filament\Office\Resources\BankAccounts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bank_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('account_number')
                    ->required()
                    // Not ->numeric(): its state cast would strip a leading zero from a NUBAN.
                    ->inputMode('numeric')
                    ->rule('digits:10')
                    ->helperText('10-digit NUBAN account number.')
                    // Unique per bank: the same account number may legitimately exist at two banks.
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('bank_name', $get('bank_name')),
                    ),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive accounts are hidden from the deposit form but keep their history.'),
            ]);
    }
}
