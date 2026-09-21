<?php

namespace App\Filament\Office\Resources\StockCounts\Pages;

use App\Filament\Office\Resources\StockCounts\StockCountResource;
use App\Models\StockCount;
use App\Services\StockCountService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewStockCount extends ViewRecord
{
    protected static string $resource = StockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        ];
    }
}
