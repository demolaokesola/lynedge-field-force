<?php

namespace App\Filament\Field\Resources\StockCounts\Pages;

use App\Filament\Field\Resources\StockCounts\StockCountResource;
use App\Filament\Shared\Resources\StockCounts\Concerns\GuardsStockCountLines;
use App\Models\Position;
use App\Models\StockCount;
use App\Services\RepScope;
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
            // Submitting locks the count. Redirects away since the edit page would
            // otherwise 403 on the next request.
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
                })
                ->successRedirectUrl(StockCountResource::getUrl('index')),
            // Hidden automatically when StockCountPolicy::delete() denies.
            DeleteAction::make(),
        ];
    }

    /**
     * Re-run the position and product guards on save. kind stays whatever it was.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $position = app(RepScope::class)->activePositions(auth()->user())
            ->firstWhere('id', (int) ($data['position_id'] ?? null));

        if (! $position instanceof Position) {
            Notification::make()->danger()->title('You do not hold that position')->send();
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
