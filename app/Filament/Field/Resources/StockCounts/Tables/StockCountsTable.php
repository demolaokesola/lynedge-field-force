<?php

namespace App\Filament\Field\Resources\StockCounts\Tables;

use App\Enums\StockCountStatus;
use App\Filament\Field\Resources\StockCounts\StockCountResource;
use App\Models\StockCount;
use App\Services\StockCountService;
use Filament\Actions\Action;
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
                    ->sortable(),
                TextColumn::make('kind')
                    ->badge(),
                TextColumn::make('count_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('posted_at')
                    ->dateTime()
                    ->placeholder('— pending'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(StockCountStatus::class),
            ])
            ->recordUrl(fn (StockCount $record): string => StockCountResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                // Hands the count to Operations; it can no longer be edited once submitted.
                Action::make('submit')
                    ->label('Submit')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Once submitted you can no longer change the count. Operations will review and post it.')
                    ->authorize('submit')
                    ->action(function (StockCount $record): void {
                        app(StockCountService::class)->submit($record);

                        Notification::make()->success()->title('Stock count submitted')->send();
                    }),
                EditAction::make(),
            ]);
    }
}
