<?php

namespace App\Filament\Shared\Resources\Distributions\Pages;

use App\Enums\DistributionStatus;
use App\Filament\Shared\Resources\Distributions\DistributionResource;
use App\Models\Distribution;
use App\Models\Position;
use App\Services\RepScope;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditDistribution extends EditRecord
{
    protected static string $resource = DistributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The submit stage: posting freezes the record (DistributionPolicy::post/
            // update/delete all require status===Draft), so this hides itself once posted.
            // Redirects away since the edit page would otherwise 403 on the next request.
            Action::make('post')
                ->label('Post')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->authorize('post')
                ->action(function (Distribution $record): void {
                    $record->status = DistributionStatus::Posted;
                    $record->save();

                    Notification::make()
                        ->success()
                        ->title('Distribution posted')
                        ->send();
                })
                ->successRedirectUrl(DistributionResource::getUrl('index')),
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
