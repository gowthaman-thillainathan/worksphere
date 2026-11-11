<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;
use Meridian\Modules\Identity\Infrastructure\Support\FrontendUrls;
use SensitiveParameter;

/**
 * "You have been given access to Meridian — set your password."
 * Queued after commit, so a rolled-back account never gets an invitation.
 */
final class AccountInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const CHANNEL_MAIL = 'mail';

    public function __construct(#[SensitiveParameter] public readonly string $token)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [self::CHANNEL_MAIL];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited to Meridian')
            ->greeting(sprintf('Hello %s,', $notifiable->name))
            ->line('An account has been created for you. Choose a password to start using Meridian.')
            ->action('Set your password', app(FrontendUrls::class)->acceptInvitation($this->token, $notifiable->email))
            ->line(sprintf(
                'This link expires in %d minutes. If it has expired, use "Forgot your password?" on the sign-in page.',
                (int) config('auth.passwords.users.expire'),
            ));
    }
}
