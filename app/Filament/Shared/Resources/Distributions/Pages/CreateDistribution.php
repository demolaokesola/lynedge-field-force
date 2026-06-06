<?php

namespace App\Filament\Shared\Resources\Distributions\Pages;

use App\Enums\DistributionStatus;
use App\Filament\Shared\Resources\Distributions\DistributionResource;
use App\Models\Position;
use App\Services\RepScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDistribution extends CreateRecord
{
    protected static string $resource = DistributionResource::class;

    /**
     * Validate the product guard and derive the denormalised columns from the resolved
     * position. line_amount and total_amount are computed by the DistributionLine observer
     * after Filament saves the relationship — nothing from the client is trusted for those.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $position = $this->resolveActivePosition($data['position_id'] ?? null);

        $data['territory_id'] = $position->territory_id;
        $data['team_id'] = $position->team_id;

        $position->load('team.products');
        $allowedIds = app(RepScope::class)->productsForPosition($position)->pluck('id')->all();

        foreach ($data['lines'] ?? [] as $line) {
            if (! in_array((int) ($line['product_id'] ?? null), $allowedIds, true)) {
                Notification::make()
                    ->danger()
                    ->title('Product not allowed')
                    ->body('One or more products do not belong to your position\'s team catalogue.')
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }

    /**
     * Force user_id, territory_id, team_id, and status onto the record — these are
     * intentionally not mass-assignable ({@see Distribution}). total_amount is set to 0
     * here and refreshed by the DistributionLine observer as lines are saved.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel());
        $record->fill($data);
        $record->user_id = auth()->id();
        $record->territory_id = $data['territory_id'];
        $record->team_id = $data['team_id'];
        $record->status = DistributionStatus::Draft;
        $record->total_amount = 0;
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
                ->body('You need an active position before you can create a distribution.')
                ->send();

            $this->halt();
        }

        return $position;
    }
}
