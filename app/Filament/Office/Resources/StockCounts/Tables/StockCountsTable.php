<?php

namespace App\Filament\Office\Resources\StockCounts\Tables;

use App\Enums\StockCountKind;
use App\Enums\StockCountStatus;
use App\Filament\Office\Resources\StockCounts\StockCountResource;
use App\Models\StockCount;
use App\Services\StockCountService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockCountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('count_date', 'desc')
            ->columns([
                TextColumn::make('position.code')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('territory.name')
                    ->sortable(),
                TextColumn::make('kind')
                    ->badge()
                    ->sortable(),
                TextColumn::make('count_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('countedBy.name')
                    ->label('Counted by')
                    ->sortable(),
                TextColumn::make('posted_at')
                    ->dateTime()
                    ->placeholder('— pending')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(StockCountStatus::class),
                SelectFilter::make('kind')
                    ->options(StockCountKind::class),
            ])
            ->recordUrl(fn (StockCount $record): string => StockCountResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                // Posting reconciles the count against the ledger and freezes it —
                // update/delete/post all require a postable status (StockCountPolicy).
                Action::make('post')
                    ->label('Post')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Each line\'s system quantity is read now; the difference is written to the ledger and the count is frozen.')
                    ->authorize('post')
                    ->action(function (StockCount $record): void {
                        app(StockCountService::class)->post($record, auth()->user());

                        Notification::make()->success()->title('Stock count posted')->send();
                    }),
                Action::make('void')
                    ->label('Void')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('void')
                    ->action(function (StockCount $record): void {
                        app(StockCountService::class)->void($record);

                        Notification::make()->success()->title('Stock count voided')->send();
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
