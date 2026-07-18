<?php

namespace App\Filament\Office\Resources\StockDispatches\Tables;

use App\Enums\StockDispatchStatus;
use App\Models\StockDispatch;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockDispatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('dispatch_date', 'desc')
            ->columns([
                TextColumn::make('position.code')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('territory.name')
                    ->sortable(),
                TextColumn::make('dispatch_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('dispatchedBy.name')
                    ->label('Dispatched by')
                    ->sortable(),
                TextColumn::make('acceptedBy.name')
                    ->label('Accepted by')
                    ->placeholder('— pending')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(StockDispatchStatus::class),
            ])
            ->recordActions([
                // Locks the dispatch for further line edits and makes it visible to the
                // rep occupying the position for acceptance.
                Action::make('send')
                    ->label('Send')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('send')
                    ->action(function (StockDispatch $record): void {
                        $record->status = StockDispatchStatus::Dispatched;
                        $record->save();

                        Notification::make()->success()->title('Dispatch sent')->send();
                    }),
                Action::make('void')
                    ->label('Void')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('void')
                    ->action(function (StockDispatch $record): void {
                        $record->status = StockDispatchStatus::Void;
                        $record->save();

                        Notification::make()->success()->title('Dispatch voided')->send();
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
