<?php

namespace App\Filament\Field\Resources\Customers\Pages;

use App\Filament\Field\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\Position;
use App\Services\RepScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    /**
     * Derive territory_id from the chosen position — never from the client. The position
     * must be one of the rep's own active positions; if the rep has none (or forges
     * another rep's position), the create is halted.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $position = $this->resolveActivePosition($data['position_id'] ?? null);

        $data['territory_id'] = $position->territory_id;
        unset($data['position_id']);

        return $data;
    }

    /**
     * Force created_by to the authenticated rep. created_by is intentionally not
     * mass-assignable ({@see Customer}), so it is set on the instance rather
     * than through create().
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel());
        $record->fill($data);
        $record->created_by = auth()->id();
        $record->save();

        return $record;
    }

    private function resolveActivePosition(int|string|null $positionId): Position
    {
        $position = app(RepScope::class)->activePositions(auth()->user())
            ->firstWhere('id', (int) $positionId);

        if (! $position instanceof Position) {
            Notification::make()
                ->danger()
                ->title('No active position')
                ->body('You need an active position before you can add a customer.')
                ->send();

            $this->halt();
        }

        return $position;
    }
}
