<?php

namespace App\Filament\Office\Resources\StockCounts\Pages;

use App\Enums\StockCountStatus;
use App\Filament\Office\Resources\StockCounts\StockCountResource;
use App\Filament\Shared\Resources\StockCounts\Concerns\GuardsStockCountLines;
use App\Models\Position;
use App\Models\StockCount;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockCount extends CreateRecord
{
    use GuardsStockCountLines;

    protected static string $resource = StockCountResource::class;

    /**
     * Validate the product guard and derive the denormalised columns from the chosen
     * position. Operations may count any active position.
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

        return $this->guardStockCountLines($position, $data);
    }

    /**
     * Force counted_by_user_id, territory_id, team_id, and status onto the record —
     * these are intentionally not mass-assignable ({@see StockCount}).
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel());
        $record->fill($data);
        $record->counted_by_user_id = auth()->id();
        $record->territory_id = $data['territory_id'];
        $record->team_id = $data['team_id'];
        $record->status = StockCountStatus::Draft;
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
