<?php

namespace App\Filament\Office\Resources\StockAdjustments\Tables;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Models\StockAdjustment;
use App\Services\StockLedger;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('adjustment_date', 'desc')
            ->columns([
                TextColumn::make('position.code')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('territory.name')
                    ->sortable(),
                TextColumn::make('adjustment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('adjustedBy.name')
                    ->label('Adjusted by')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(StockAdjustmentStatus::class),
            ])
            ->recordActions([
                // The submit stage: posting freezes the record and moves stock via
                // StockLedger — update/delete/post all require status===Draft.
                Action::make('post')
                    ->label('Post')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('post')
                    ->action(function (StockAdjustment $record): void {
                        DB::transaction(function () use ($record): void {
                            $record->load('lines.product', 'position');

                            foreach ($record->lines as $line) {
                                app(StockLedger::class)->record(
                                    $record->position,
                                    $line->product,
                                    (string) $line->quantity_delta,
                                    StockMovementType::Adjustment,
                                    $line,
                                    auth()->user(),
                                );
                            }

                            $record->status = StockAdjustmentStatus::Posted;
                            $record->save();
                        });

                        Notification::make()->success()->title('Adjustment posted')->send();
                    }),
                Action::make('void')
                    ->label('Void')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('void')
                    ->action(function (StockAdjustment $record): void {
                        $record->status = StockAdjustmentStatus::Void;
                        $record->save();

                        Notification::make()->success()->title('Adjustment voided')->send();
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
