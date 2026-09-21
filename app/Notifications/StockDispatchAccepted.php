<?php

namespace App\Notifications;

use App\Filament\Office\Resources\StockDispatches\StockDispatchResource;
use App\Models\StockDispatch;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to Operations when the rep accepts a dispatch into their position's balance.
 */
class StockDispatchAccepted extends Notification
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
        $this->dispatch->loadMissing(['position', 'acceptedBy']);

        return FilamentNotification::make()
            ->title('Dispatch accepted')
            ->body("{$this->dispatch->acceptedBy?->name} accepted dispatch #{$this->dispatch->id} for position {$this->dispatch->position->code}.")
            ->icon('heroicon-o-check-circle')
            ->success()
            ->actions([
                Action::make('view')
                    ->label('View dispatches')
                    ->button()
                    ->url(StockDispatchResource::getUrl('index', panel: 'office'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
