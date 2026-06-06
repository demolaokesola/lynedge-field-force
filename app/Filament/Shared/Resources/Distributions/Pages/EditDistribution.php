<?php

namespace App\Filament\Shared\Resources\Distributions\Pages;

use App\Filament\Shared\Resources\Distributions\DistributionResource;
use App\Models\Position;
use App\Services\RepScope;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDistribution extends EditRecord
{
    protected static string $resource = DistributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden automatically when DistributionPolicy::delete() denies.
            DeleteAction::make(),
        ];
    }

    /**
     * Re-run the product guard on save. line_amount and total_amount are recomputed by
     * the DistributionLine observer — no client values are trusted for those.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $position = app(RepScope::class)->activePositions(auth()->user())
            ->firstWhere('id', (int) ($data['position_id'] ?? null));

        if (! $position instanceof Position) {
            Notification::make()
                ->danger()
                ->title('No active position')
                ->body('You need an active position to save this distribution.')
                ->send();

            $this->halt();
        }

        $position->load('team.products');
        $allowedIds = app(RepScope::class)->productsForPosition($position)->pluck('id')->all();

        foreach ($data['lines'] ?? [] as $line) {
            if (! \in_array((int) ($line['product_id'] ?? null), $allowedIds, true)) {
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
}
