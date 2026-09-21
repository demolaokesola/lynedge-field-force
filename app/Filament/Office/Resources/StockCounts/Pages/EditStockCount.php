<?php

namespace App\Filament\Office\Resources\StockCounts\Pages;

use App\Filament\Office\Resources\StockCounts\StockCountResource;
use App\Filament\Shared\Resources\StockCounts\Concerns\GuardsStockCountLines;
use App\Models\Position;
use App\Models\StockCount;
use App\Services\StockCountService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EditStockCount extends EditRecord
{
    use GuardsStockCountLines;

    protected static string $resource = StockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Posting freezes the record. Redirects away since the edit page would
            // otherwise 403 on the next request.
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
                })
                ->successRedirectUrl(StockCountResource::getUrl('index')),
            // Hidden automatically when StockCountPolicy::delete() denies.
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

        return $this->guardStockCountLines($position, $data);
    }

    /**
     * territory_id and team_id are not mass-assignable, so re-derive them here in case
     * the position changed.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data);
        $record->territory_id = $data['territory_id'];
        $record->team_id = $data['team_id'];
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
