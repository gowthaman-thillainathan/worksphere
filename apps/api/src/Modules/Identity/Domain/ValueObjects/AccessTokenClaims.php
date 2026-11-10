<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\ValueObjects;

use DateTimeImmutable;

/**
 * The verified claims of a JWT access token.
 *
 * - subject      user id (sub)
 * - tokenId      unique token id, used for early revocation (jti)
 * - sessionId    refresh-token family the token was issued for (sid)
 * - tokenVersion user's token version at issue time; bumping it invalidates
 *                every outstanding token of that user (ver)
 */
final readonly class AccessTokenClaims
{
    /**
     * Request attribute under which the authenticated request's claims are stored.
     */
    public const REQUEST_ATTRIBUTE = 'identity.access_token_claims';

    public function __construct(
        public string $subject,
        public string $tokenId,
        public string $sessionId,
        public int $tokenVersion,
        public DateTimeImmutable $issuedAt,
        public DateTimeImmutable $expiresAt,
    ) {}

    public function secondsUntilExpiry(DateTimeImmutable $now): int
    {
        return max(0, $this->expiresAt->getTimestamp() - $now->getTimestamp());
    }
}
