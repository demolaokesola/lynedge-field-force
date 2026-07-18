<?php

namespace App\Filament\Office\Resources\StockDispatches\Pages;

use App\Enums\StockDispatchStatus;
use App\Filament\Office\Resources\StockDispatches\StockDispatchResource;
use App\Models\Position;
use App\Models\StockDispatch;
use App\Services\RepScope;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditStockDispatch extends EditRecord
{
    protected static string $resource = StockDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Locks the dispatch for further line edits and makes it visible to the
            // rep occupying the position for acceptance. Redirects away since the edit
            // page would otherwise 403 on the next request.
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
                })
                ->successRedirectUrl(StockDispatchResource::getUrl('index')),
            // Hidden automatically when StockDispatchPolicy::delete() denies.
            DeleteAction::make(),
        ];
    }

    /**
     * Re-run the product guard on save.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $position = Position::find($data['position_id'] ?? null);

        if (! $position instanceof Position) {
            Notification::make()->danger()->title('Position not found')->send();
            $this->halt();
        }

        $data['territory_id'] = $position->territory_id;
        $data['team_id'] = $position->team_id;

        $position->load('team.products');
        $allowedIds = app(RepScope::class)->productsForPosition($position)->pluck('id')->all();

        foreach ($data['lines'] ?? [] as $line) {
            if (! in_array((int) ($line['product_id'] ?? null), $allowedIds, true)) {
                Notification::make()
                    ->danger()
                    ->title('Product not allowed')
                    ->body('One or more products do not belong to this position\'s team catalogue.')
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }
}
