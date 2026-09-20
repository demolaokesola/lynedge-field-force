<?php

namespace App\Filament\Shared\Resources\Deposits\Tables;

use App\Enums\DepositChannel;
use App\Enums\DepositStatus;
use App\Filament\Shared\Resources\Deposits\Actions\DisputeActions;
use App\Filament\Shared\Resources\Deposits\Actions\ReconciliationActions;
use App\Filament\Shared\Resources\Deposits\DepositResource;
use App\Models\BankAccount;
use App\Models\Deposit;
use App\Support\Money;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('deposit_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['customer', 'user', 'territory', 'bankAccount', 'reconciledBy']))
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
                TextColumn::make('bankAccount.bank_name')
                    ->label('Bank Account')
                    ->description(fn (Deposit $record): ?string => $record->bankAccount?->account_number)
                    ->sortable(),
                TextColumn::make('reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')
                    ->label('Amount (₦)')
                    ->formatStateUsing(fn (?Money $state): ?string => $state === null ? null : number_format((float) $state->amount, 2))
                    ->sortable(),
                TextColumn::make('channel')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('statement_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('statement_reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reconciledBy.name')
                    ->label('Reconciled By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dispute_reason')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DepositStatus::class),
                SelectFilter::make('bank_account_id')
                    ->label('Bank Account')
                    ->relationship('bankAccount', 'account_number')
                    ->getOptionLabelFromRecordUsing(fn (BankAccount $record): string => $record->label)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('channel')
                    ->options(DepositChannel::class),
                SelectFilter::make('territory_id')
                    ->label('Territory')
                    ->relationship('territory', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('deposit_date')
                    ->schema([
                        DatePicker::make('from')
                            ->native(false),
                        DatePicker::make('until')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('deposit_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('deposit_date', '<=', $date))),
            ])
            // Rows open the read-only view page; reps have no other way into a submitted deposit.
            ->recordUrl(fn (Deposit $record): string => DepositResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                // Hidden automatically when DepositPolicy::update() denies (field roles).
                EditAction::make(),
                // Reconciliation and dispute actions are policy-gated (update) so field roles never see them.
                ActionGroup::make([
                    ...ReconciliationActions::make(),
                    ...DisputeActions::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
