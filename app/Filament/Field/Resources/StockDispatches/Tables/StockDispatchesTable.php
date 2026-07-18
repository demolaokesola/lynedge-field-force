<?php

namespace App\Filament\Field\Resources\StockDispatches\Tables;

use App\Enums\StockDispatchStatus;
use App\Enums\StockMovementType;
use App\Filament\Field\Resources\StockDispatches\StockDispatchResource;
use App\Models\StockDispatch;
use App\Services\StockLedger;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class StockDispatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('dispatch_date', 'desc')
            ->columns([
                TextColumn::make('position.code')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('dispatch_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('dispatchedBy.name')
                    ->label('Dispatched by'),
                TextColumn::make('accepted_at')
                    ->dateTime()
                    ->placeholder('— pending'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(StockDispatchStatus::class),
            ])
            ->recordUrl(fn (StockDispatch $record): string => StockDispatchResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('accept')
                    ->action(function (StockDispatch $record): void {
                        DB::transaction(function () use ($record): void {
                            $record->load('lines.product', 'position');

                            foreach ($record->lines as $line) {
                                app(StockLedger::class)->record(
                                    $record->position,
                                    $line->product,
                                    (string) $line->quantity,
                                    StockMovementType::DispatchAcceptance,
                                    $line,
                                    auth()->user(),
                                );
                            }

                            $record->status = StockDispatchStatus::Accepted;
                            $record->accepted_by_user_id = auth()->id();
                            $record->accepted_at = now();
                            $record->save();
                        });

                        Notification::make()->success()->title('Stock accepted')->send();
                    }),
            ]);
    }
}
