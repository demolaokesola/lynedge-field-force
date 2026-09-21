<?php

namespace App\Filament\Field\Resources\StockCounts\Pages;

use App\Filament\Field\Resources\StockCounts\StockCountResource;
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
        ];
    }
}
