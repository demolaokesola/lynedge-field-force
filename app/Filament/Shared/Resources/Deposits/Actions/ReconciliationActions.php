<?php

namespace App\Filament\Shared\Resources\Deposits\Actions;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * "Reconcile" / "Undo reconciliation" actions shared by the deposit edit page, the
 * deposits table and the Bank Reconciliation page. Both are gated by
 * DepositPolicy::update (accountant | platform_admin), so reps never see them.
 */
class ReconciliationActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        return [
            self::reconcile(),
            self::undo(),
        ];
    }

    /**
     * Match the deposit to a single bank-statement entry.
     */
    public static function reconcile(): Action
    {
        return Action::make('reconcile')
            ->label('Reconcile')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->authorize('update')
            ->visible(fn (Deposit $record): bool => $record->status === DepositStatus::Unreconciled)
            ->modalHeading('Reconcile deposit')
            ->modalDescription('Confirm this deposit matches an amount on the bank statement.')
            ->schema([
                DatePicker::make('statement_date')
                    ->label('Statement date')
                    ->required()
                    ->native(false)
                    ->default(fn (Deposit $record): ?string => $record->deposit_date?->toDateString()),
                TextInput::make('statement_reference')
                    ->label('Statement reference')
                    ->maxLength(100),
            ])
            ->action(function (Deposit $record, array $data): void {
                $record->reconcile(
                    auth()->user(),
                    Carbon::parse($data['statement_date']),
                    filled($data['statement_reference'] ?? null) ? $data['statement_reference'] : null,
                );

                Notification::make()
                    ->title('Deposit reconciled')
                    ->success()
                    ->send();
            });
    }

    /**
     * Clear the statement match and return the deposit to the unreconciled queue.
     */
    public static function undo(): Action
    {
        return Action::make('undoReconciliation')
            ->label('Undo reconciliation')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('The statement match will be cleared and the deposit returned to Unreconciled.')
            ->authorize('update')
            ->visible(fn (Deposit $record): bool => $record->status === DepositStatus::Reconciled)
            ->action(function (Deposit $record): void {
                $record->unreconcile();

                Notification::make()
                    ->title('Reconciliation undone')
                    ->warning()
                    ->send();
            });
    }
}
