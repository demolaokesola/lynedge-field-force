<?php

namespace App\Filament\Shared\Resources\Distributions\Tables;

use App\Enums\DistributionStatus;
use App\Models\Distribution;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DistributionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('invoice_date', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->sortable(),
                TextColumn::make('territory.name')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                // Money cast yields a Money object, which ->numeric() skips (not is_numeric),
                // so format the raw amount ourselves; the symbol lives in the header.
                TextColumn::make('total_amount')
                    ->label('Total (₦)')
                    ->formatStateUsing(fn (?Money $state): ?string => $state === null ? null : number_format((float) $state->amount, 2))
                    ->alignRight()
                    ->sortable(),
                // A plain rep only ever sees their own rows (ScopesToViewer), so the Rep
                // column is noise for them; supervisors and management roles see others' rows.
                TextColumn::make('user.name')
                    ->label('Rep')
                    ->sortable()
                    ->hidden(fn (): bool => auth()->user()->hasRole('sales_rep') && ! auth()->user()->isSupervisor()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DistributionStatus::class),
            ])
            ->recordActions([
                // The submit stage: posting freezes the record (DistributionPolicy::post/
                // update/delete all require status===Draft), so this hides itself once posted.
                Action::make('post')
                    ->label('Post')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('post')
                    ->action(function (Distribution $record): void {
                        $record->status = DistributionStatus::Posted;
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Distribution posted')
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
