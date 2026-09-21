<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a platform admin creates (or re-invites) a user. Carries the invitation
 * token issued by the 'invitations' password broker, which expires after 24 hours.
 */
class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited to '.config('app.name'))
            ->greeting("Hello {$notifiable->name},")
            ->line('An account has been created for you on '.config('app.name').'. Set a password to get started.')
            ->action('Set your password', $this->url($notifiable))
            ->line('This link expires in 24 hours. If it has expired, ask your administrator to resend the invitation.');
    }

    public function url(User $notifiable): string
    {
        return route('invitation.accept', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);
    }
}
