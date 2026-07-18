<?php

namespace App\Filament\Office\Resources\StockDispatches\Pages;

use App\Enums\StockDispatchStatus;
use App\Filament\Office\Resources\StockDispatches\StockDispatchResource;
use App\Models\Position;
use App\Models\StockDispatch;
use App\Services\RepScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockDispatch extends CreateRecord
{
    protected static string $resource = StockDispatchResource::class;

    /**
     * Validate the product guard and derive the denormalised columns from the chosen
     * position. Operations may dispatch to any active position, not just their own.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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

    /**
     * Force dispatched_by_user_id, territory_id, team_id, and status onto the record —
     * these are intentionally not mass-assignable ({@see StockDispatch}).
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel());
        $record->fill($data);
        $record->dispatched_by_user_id = auth()->id();
        $record->territory_id = $data['territory_id'];
        $record->team_id = $data['team_id'];
        $record->status = StockDispatchStatus::Draft;
        $record->save();

        return $record;
    }
}
