<?php

namespace App\Notifications;

use App\Filament\Office\Resources\StockCounts\StockCountResource;
use App\Models\StockCount;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to Operations when a rep submits a stock count for review and posting.
 */
class StockCountSubmitted extends Notification
{
    use Queueable;

    public function __construct(public StockCount $count) {}

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
        $this->count->loadMissing(['position', 'countedBy']);

        return FilamentNotification::make()
            ->title('Stock count submitted')
            ->body("{$this->count->countedBy?->name} submitted a count for position {$this->count->position->code} dated {$this->count->count_date->toFormattedDateString()}.")
            ->icon('heroicon-o-clipboard-document-check')
            ->warning()
            ->actions([
                Action::make('review')
                    ->label('Review count')
                    ->button()
                    ->url(StockCountResource::getUrl('view', ['record' => $this->count], panel: 'office'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
