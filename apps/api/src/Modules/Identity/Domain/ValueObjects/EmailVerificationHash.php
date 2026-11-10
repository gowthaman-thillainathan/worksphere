<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\ValueObjects;

/**
 * Binds a verification link to the address it was sent to: if the user's
 * e-mail changes, links issued for the old address stop working.
 */
final readonly class EmailVerificationHash
{
    private const ALGORITHM = 'sha1';

    public static function of(string $email): string
    {
        return hash(self::ALGORITHM, $email);
    }

    public static function matches(string $email, string $hash): bool
    {
        return hash_equals(self::of($email), $hash);
    }
}
