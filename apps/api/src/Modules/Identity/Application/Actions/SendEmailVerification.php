<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

final readonly class SendEmailVerification
{
    public function handle(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
