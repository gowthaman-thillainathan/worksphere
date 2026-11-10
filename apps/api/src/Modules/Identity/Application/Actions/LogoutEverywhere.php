<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Identity\Domain\Events\UserLoggedOut;
use Meridian\Modules\Identity\Domain\Repositories\RefreshTokenRepository;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

/**
 * Ends every session of the user on every device.
 *
 * Bumping the token version invalidates all outstanding access tokens at
 * once without having to know their ids.
 */
final readonly class LogoutEverywhere
{
    public function __construct(
        private UserRepository $users,
        private RefreshTokenRepository $refreshTokens,
        private ConnectionInterface $database,
        private Dispatcher $events,
    ) {}

    public function handle(User $user, AccessTokenClaims $claims): void
    {
        $this->database->transaction(function () use ($user): void {
            $user->forceFill(['token_version' => $user->token_version + 1]);
            $this->users->save($user);
            $this->refreshTokens->revokeAllForUser($user->id, CarbonImmutable::now());
        });

        $this->events->dispatch(new UserLoggedOut($user->id, $claims->sessionId, everywhere: true));
    }
}
