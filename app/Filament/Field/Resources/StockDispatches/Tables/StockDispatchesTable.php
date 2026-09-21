<?php

namespace App\Filament\Field\Resources\StockDispatches\Tables;

use App\Enums\StockDispatchStatus;
use App\Filament\Field\Resources\StockDispatches\StockDispatchResource;
use App\Models\StockDispatch;
use App\Services\StockDispatchService;
use Filament\Actions\Action;
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
                        app(StockDispatchService::class)->accept($record, auth()->user());

                        Notification::make()->success()->title('Stock accepted')->send();
                    }),
            ]);
    }
}
