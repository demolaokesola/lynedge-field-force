<?php

namespace App\Filament\Shared\Resources\Deposits\Schemas;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Read-only deposit details. This is the rep's only window into a deposit after
 * submission, so the dispute reason is surfaced prominently when the deposit is disputed.
 */
class DepositInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dispute')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->iconColor('danger')
                    ->description('This deposit has been flagged by the accounts team.')
                    ->visible(fn (Deposit $record): bool => $record->status === DepositStatus::Disputed)
                    ->schema([
                        TextEntry::make('dispute_reason')
                            ->label('Dispute reason')
                            ->color('danger')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Deposit')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer.name')
                            ->label('Customer'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('amount')
                            ->label('Amount (₦)')
                            ->formatStateUsing(fn (?Money $state): ?string => $state === null ? null : number_format((float) $state->amount, 2)),
                        TextEntry::make('deposit_date')
                            ->date(),
                        TextEntry::make('bankAccount.label')
                            ->label('Bank Account'),
                        TextEntry::make('channel')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('reference')
                            ->placeholder('—'),
                        TextEntry::make('user.name')
                            ->label('Received By'),
                        TextEntry::make('territory.name')
                            ->label('Territory'),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Reconciliation')
                    ->columns(2)
                    ->visible(fn (Deposit $record): bool => $record->reconciled_at !== null)
                    ->schema([
                        TextEntry::make('statement_date')
                            ->date(),
                        TextEntry::make('statement_reference')
                            ->placeholder('—'),
                        TextEntry::make('reconciledBy.name')
                            ->label('Reconciled By'),
                        TextEntry::make('reconciled_at')
                            ->dateTime(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
