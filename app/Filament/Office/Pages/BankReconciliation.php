<?php

namespace App\Filament\Office\Pages;

use App\Filament\Shared\Resources\Deposits\Actions\ReconciliationActions;
use App\Filament\Shared\Resources\Deposits\DepositResource;
use App\Models\BankAccount;
use App\Models\Deposit;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The accountant's reconciliation queue: every deposit still awaiting a
 * bank-statement match, narrowed by bank account. Reconciling a row removes it
 * from the queue; the full deposit record stays in the Deposits resource.
 */
class BankReconciliation extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Bank Reconciliation';

    protected string $view = 'filament.office.pages.bank-reconciliation';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['superuser', 'accountant']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Deposit::query()
                ->visibleTo(auth()->user())
                ->unreconciled()
                ->with(['customer', 'user', 'territory', 'bankAccount']))
            ->defaultSort('deposit_date')
            ->columns([
                TextColumn::make('deposit_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('bankAccount.bank_name')
                    ->label('Bank Account')
                    ->description(fn (Deposit $record): ?string => $record->bankAccount?->account_number)
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Rep')
                    ->sortable(),
                TextColumn::make('territory.name')
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Bank Ref')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount (₦)')
                    ->formatStateUsing(fn (?Money $state): ?string => $state === null ? null : number_format((float) $state->amount, 2))
                    ->sortable(),
                TextColumn::make('channel')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('bank_account_id')
                    ->label('Bank Account')
                    ->relationship('bankAccount', 'account_number')
                    ->getOptionLabelFromRecordUsing(fn (BankAccount $record): string => $record->label)
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ReconciliationActions::reconcile()
                    ->button(),
                Action::make('open')
                    ->label('Open')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Deposit $record): string => DepositResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Nothing to reconcile')
            ->emptyStateDescription('Every deposit has been matched to the bank statement.');
    }
}
