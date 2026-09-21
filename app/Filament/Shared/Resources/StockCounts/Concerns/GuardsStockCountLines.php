<?php

namespace App\Filament\Shared\Resources\StockCounts\Concerns;

use App\Models\Position;
use App\Services\RepScope;
use Filament\Notifications\Notification;

/**
 * The stock-count product guard, shared by the Office and Field create/edit pages:
 * every line's product must belong to the position's team catalogue, and the
 * denormalised territory_id/team_id are derived from the position — never the client.
 */
trait GuardsStockCountLines
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function guardStockCountLines(Position $position, array $data): array
    {
        $data['territory_id'] = $position->territory_id;
        $data['team_id'] = $position->team_id;

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
