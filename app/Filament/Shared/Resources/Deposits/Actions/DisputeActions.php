<?php

namespace App\Filament\Shared\Resources\Deposits\Actions;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * "Mark disputed" / "Clear dispute" actions shared by the deposit view and edit pages
 * and the deposits table. Both are gated by DepositPolicy::update (accountant |
 * platform_admin), so reps never see them even though the resource is shared with the
 * field panel. Disputing requires a reason, which the rep reads on the view page.
 */
class DisputeActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        return [
            Action::make('markDisputed')
                ->label('Mark disputed')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->modalHeading('Mark deposit as disputed')
                ->modalDescription('The rep will see this reason on the deposit. The status is frozen as Disputed until the dispute is cleared.')
                ->schema([
                    Textarea::make('dispute_reason')
                        ->label('Reason')
                        ->required()
                        ->maxLength(1000)
                        ->rows(3),
                ])
                ->authorize('update')
                // A reconciled deposit is already matched to the statement; undo the reconciliation first.
                ->visible(fn (Deposit $record): bool => $record->status === DepositStatus::Unreconciled)
                ->action(function (Deposit $record, array $data): void {
                    $record->markDisputed($data['dispute_reason']);

                    Notification::make()
                        ->title('Deposit marked as disputed')
                        ->warning()
                        ->send();
                }),
            Action::make('clearDispute')
                ->label('Clear dispute')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('The deposit will return to the unreconciled queue.')
                ->authorize('update')
                ->visible(fn (Deposit $record): bool => $record->status === DepositStatus::Disputed)
                ->action(function (Deposit $record): void {
                    $record->clearDispute();

                    Notification::make()
                        ->title('Dispute cleared')
                        ->success()
                        ->send();
                }),
        ];
    }
}
