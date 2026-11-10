<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Services;

use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;

/**
 * Deny-list for individual access tokens revoked before they expire
 * (e.g. on logout). Entries only need to live until the token's expiry.
 */
interface AccessTokenRevocationList
{
    public function revoke(AccessTokenClaims $claims): void;

    public function isRevoked(string $tokenId): bool;
}
