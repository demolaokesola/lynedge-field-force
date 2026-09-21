<?php

namespace App\Notifications;

use App\Filament\Field\Resources\StockDispatches\StockDispatchResource;
use App\Models\StockDispatch;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the rep occupying the destination position when Operations sends a
 * dispatch — it is now waiting for them to accept in the Field panel.
 */
class StockDispatchSent extends Notification
{
    use Queueable;

    public function __construct(public StockDispatch $dispatch) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        $this->dispatch->loadMissing('position');

        return FilamentNotification::make()
            ->title('Stock dispatched to you')
            ->body("Dispatch #{$this->dispatch->id} for position {$this->dispatch->position->code} is waiting for your acceptance.")
            ->icon('heroicon-o-paper-airplane')
            ->info()
            ->actions([
                Action::make('view')
                    ->label('View dispatch')
                    ->button()
                    ->url(StockDispatchResource::getUrl('view', ['record' => $this->dispatch], panel: 'field'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
