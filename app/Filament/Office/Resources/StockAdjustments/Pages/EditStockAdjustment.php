<?php

namespace App\Filament\Office\Resources\StockAdjustments\Pages;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Filament\Office\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\Position;
use App\Models\StockAdjustment;
use App\Services\RepScope;
use App\Services\StockLedger;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class EditStockAdjustment extends EditRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The submit stage: posting freezes the record and moves stock via
            // StockLedger. Redirects away since the edit page would otherwise 403 on
            // the next request.
            Action::make('post')
                ->label('Post')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->authorize('post')
                ->action(function (StockAdjustment $record): void {
                    DB::transaction(function () use ($record): void {
                        $record->load('lines.product', 'position');

                        foreach ($record->lines as $line) {
                            app(StockLedger::class)->record(
                                $record->position,
                                $line->product,
                                (string) $line->quantity_delta,
                                StockMovementType::Adjustment,
                                $line,
                                auth()->user(),
                            );
                        }

                        $record->status = StockAdjustmentStatus::Posted;
                        $record->save();
                    });

                    Notification::make()->success()->title('Adjustment posted')->send();
                })
                ->successRedirectUrl(StockAdjustmentResource::getUrl('index')),
            // Hidden automatically when StockAdjustmentPolicy::delete() denies.
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
}
