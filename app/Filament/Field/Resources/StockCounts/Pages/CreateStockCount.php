<?php

namespace App\Filament\Field\Resources\StockCounts\Pages;

use App\Enums\StockCountKind;
use App\Enums\StockCountStatus;
use App\Filament\Field\Resources\StockCounts\StockCountResource;
use App\Filament\Shared\Resources\StockCounts\Concerns\GuardsStockCountLines;
use App\Models\Position;
use App\Services\RepScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockCount extends CreateRecord
{
    use GuardsStockCountLines;

    protected static string $resource = StockCountResource::class;

    /**
     * A rep may only count a position they currently hold, and only as a Periodic
     * count — kind is not on their form and is forced in handleRecordCreation().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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
     * Force counted_by_user_id, territory_id, team_id, kind, and status onto the
     * record — these are intentionally not mass-assignable from a rep's form.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel());
        $record->fill($data);
        $record->kind = StockCountKind::Periodic;
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
