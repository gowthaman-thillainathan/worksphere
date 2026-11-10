<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;

/**
 * Sends a password reset e-mail to active accounts.
 *
 * The outcome is intentionally not reported to the caller: the API answers
 * the same way whether or not the address belongs to an account.
 */
final readonly class SendPasswordResetLink
{
    public function __construct(
        private UserRepository $users,
        private PasswordBrokerFactory $passwordBrokers,
    ) {}

    public function handle(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || ! $user->status->canAuthenticate()) {
            return;
        }

        // The broker throttles repeated requests per account (auth.passwords.users.throttle).
        $this->passwordBrokers->broker()->sendResetLink(['email' => $user->email]);
    }
}
