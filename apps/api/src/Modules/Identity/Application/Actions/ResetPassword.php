<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Identity\Application\Data\ResetPasswordData;
use Meridian\Modules\Identity\Domain\Events\PasswordWasReset;
use Meridian\Modules\Identity\Domain\Exceptions\InvalidPasswordResetToken;
use Meridian\Modules\Identity\Domain\Repositories\RefreshTokenRepository;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;
use SensitiveParameter;

/**
 * Sets a new password from a single-use reset token and signs the user out
 * everywhere. Completing a reset also proves ownership of the e-mail address
 * and clears any lockout.
 */
final readonly class ResetPassword
{
    public function __construct(
        private PasswordBrokerFactory $passwordBrokers,
        private UserRepository $users,
        private RefreshTokenRepository $refreshTokens,
        private ConnectionInterface $database,
        private Dispatcher $events,
    ) {}

    /**
     * @throws InvalidPasswordResetToken
     */
    public function handle(ResetPasswordData $data): void
    {
        $resetUser = null;

        $status = $this->passwordBrokers->broker()->reset(
            ['email' => $data->email, 'token' => $data->token, 'password' => $data->password],
            function (User $user, #[SensitiveParameter] string $password) use (&$resetUser): void {
                $this->applyNewPassword($user, $password);
                $resetUser = $user;
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET || $resetUser === null) {
            throw new InvalidPasswordResetToken;
        }

        $this->events->dispatch(new PasswordWasReset($resetUser->id));
    }

    private function applyNewPassword(User $user, #[SensitiveParameter] string $password): void
    {
        $now = CarbonImmutable::now();

        $this->database->transaction(function () use ($user, $password, $now): void {
            $user->forceFill([
                'password' => $password,
                'password_changed_at' => $now,
                'token_version' => $user->token_version + 1,
                'failed_login_count' => 0,
                'locked_until' => null,
                'email_verified_at' => $user->email_verified_at ?? $now,
            ]);

            $this->users->save($user);
            $this->refreshTokens->revokeAllForUser($user->id, $now);
        });
    }
}
