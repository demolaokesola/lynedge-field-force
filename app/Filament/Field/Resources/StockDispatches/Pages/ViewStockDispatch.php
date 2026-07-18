<?php

namespace App\Filament\Field\Resources\StockDispatches\Pages;

use App\Enums\StockDispatchStatus;
use App\Enums\StockMovementType;
use App\Filament\Field\Resources\StockDispatches\StockDispatchResource;
use App\Models\StockDispatch;
use App\Services\StockLedger;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

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
        ];
    }
}
