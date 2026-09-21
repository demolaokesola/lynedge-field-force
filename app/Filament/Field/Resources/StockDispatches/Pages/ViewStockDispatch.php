<?php

namespace App\Filament\Field\Resources\StockDispatches\Pages;

use App\Filament\Field\Resources\StockDispatches\StockDispatchResource;
use App\Models\StockDispatch;
use App\Services\StockDispatchService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewStockDispatch extends ViewRecord
{
    protected static string $resource = StockDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        ];
    }
}
