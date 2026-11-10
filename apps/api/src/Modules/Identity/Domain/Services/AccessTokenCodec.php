<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Services;

use Meridian\Modules\Identity\Domain\Exceptions\InvalidAccessToken;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;

/**
 * Port for signing and verifying access tokens. The JWT library is an
 * infrastructure detail behind this interface.
 */
interface AccessTokenCodec
{
    public function encode(AccessTokenClaims $claims): string;

    /**
     * Verifies signature, algorithm, issuer, audience and time-based claims.
     *
     * @throws InvalidAccessToken
     */
    public function decode(string $token): AccessTokenClaims;
}
