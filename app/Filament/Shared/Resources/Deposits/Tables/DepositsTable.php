<?php

namespace App\Filament\Shared\Resources\Deposits\Tables;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('deposit_date', 'desc')
            ->columns([
                TextColumn::make('deposit_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Received By')
                    ->sortable(),
                TextColumn::make('territory.name')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount (₦)')
                    ->sortable(),
                TextColumn::make('remaining_balance')
                    ->label('Balance (₦)')
                    ->state(fn (Deposit $record): string => $record->remainingBalance()->format()),
                TextColumn::make('channel')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DepositStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
