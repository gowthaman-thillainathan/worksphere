<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

/**
 * Token lifetimes and strength, built from config/identity.php.
 */
final readonly class TokenPolicy
{
    public function __construct(
        public int $accessTokenTtlSeconds,
        public int $refreshTokenTtlSeconds,
        public int $refreshTokenEntropyBytes,
    ) {}
}
