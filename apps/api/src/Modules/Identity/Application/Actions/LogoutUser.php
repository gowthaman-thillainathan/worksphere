<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Meridian\Modules\Identity\Domain\Events\UserLoggedOut;
use Meridian\Modules\Identity\Domain\Repositories\RefreshTokenRepository;
use Meridian\Modules\Identity\Domain\Services\AccessTokenRevocationList;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;

/**
 * Ends the current session: its refresh-token family can no longer be used
 * and the presented access token is revoked immediately.
 */
final readonly class LogoutUser
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private AccessTokenRevocationList $revocations,
        private Dispatcher $events,
    ) {}

    public function handle(AccessTokenClaims $claims): void
    {
        $this->refreshTokens->revokeFamily($claims->sessionId, CarbonImmutable::now());
        $this->revocations->revoke($claims);

        $this->events->dispatch(new UserLoggedOut($claims->subject, $claims->sessionId, everywhere: false));
    }
}
