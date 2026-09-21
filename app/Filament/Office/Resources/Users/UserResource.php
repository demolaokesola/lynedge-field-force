<?php

namespace App\Filament\Office\Resources\Users;

use App\Filament\Office\Resources\Users\Pages\CreateUser;
use App\Filament\Office\Resources\Users\Pages\EditUser;
use App\Filament\Office\Resources\Users\Pages\ListUsers;
use App\Filament\Office\Resources\Users\Schemas\UserForm;
use App\Filament\Office\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Re-issues the onboarding link for a user who has not yet set a password. Shared
     * by the table row action and the edit page header.
     */
    public static function resendInvitationAction(): Action
    {
        return Action::make('resendInvitation')
            ->label('Resend invitation')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('warning')
            ->visible(fn (User $record): bool => ! $record->hasSetPassword())
            ->authorize('invite')
            ->requiresConfirmation()
            ->modalHeading('Resend invitation')
            ->modalDescription(fn (User $record): string => "A new 24-hour link will be emailed to {$record->email}. Any earlier link stops working.")
            ->action(function (User $record): void {
                $record->sendInvitation();

                Notification::make()
                    ->success()
                    ->title("Invitation sent to {$record->email}")
                    ->send();
            });
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
