<?php

namespace App\Filament\Office\Resources\Users\Pages;

use App\Filament\Office\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Admins never set passwords: the new user receives a 24-hour invitation link.
     */
    protected function afterCreate(): void
    {
        $this->getRecord()->sendInvitation();

        Notification::make()
            ->success()
            ->title("Invitation sent to {$this->getRecord()->email}")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
