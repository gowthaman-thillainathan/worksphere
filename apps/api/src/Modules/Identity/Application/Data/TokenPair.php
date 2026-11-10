<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

use DateTimeImmutable;
use Meridian\Modules\Identity\Domain\ValueObjects\RefreshTokenSecret;

/**
 * A freshly issued access token and the refresh token that can renew it.
 */
final readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public DateTimeImmutable $accessTokenExpiresAt,
        public RefreshTokenSecret $refreshToken,
        public DateTimeImmutable $refreshTokenExpiresAt,
        public string $sessionId,
        public string $refreshTokenId,
    ) {}

    public function accessTokenLifetime(DateTimeImmutable $now): int
    {
        return max(0, $this->accessTokenExpiresAt->getTimestamp() - $now->getTimestamp());
    }
}
