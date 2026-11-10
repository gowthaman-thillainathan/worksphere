<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Meridian\Modules\Identity\Domain\Events\EmailWasVerified;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Domain\ValueObjects\EmailVerificationHash;

/**
 * Confirms an e-mail address from a verification link. The link's signature
 * and expiry are checked by the HTTP layer; this checks that the link was
 * issued for the user's current address.
 */
final readonly class VerifyEmail
{
    public function __construct(
        private UserRepository $users,
        private Dispatcher $events,
    ) {}

    /**
     * @return bool Whether the address is verified after the call.
     */
    public function handle(string $userId, string $addressHash): bool
    {
        $user = $this->users->findById($userId);

        if ($user === null || ! EmailVerificationHash::matches($user->getEmailForVerification(), $addressHash)) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $this->events->dispatch(new EmailWasVerified($user->id));
        }

        return true;
    }
}
