<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Notifications;

use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Meridian\Modules\Identity\Infrastructure\Support\FrontendUrls;

final class AccountLockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const CHANNEL_MAIL = 'mail';

    public function __construct(public readonly DateTimeImmutable $lockedUntil) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [self::CHANNEL_MAIL];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account has been temporarily locked')
            ->line('We locked your account after several unsuccessful sign-in attempts.')
            ->line(sprintf('You can try again after %s UTC.', $this->lockedUntil->format('Y-m-d H:i')))
            ->line('If this was not you, reset your password now.')
            ->action('Reset password', app(FrontendUrls::class)->forgotPassword());
    }
}
