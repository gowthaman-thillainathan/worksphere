<?php

declare(strict_types=1);

namespace Meridian\Shared\Domain\Exceptions;

/**
 * Optimistic-locking failure: the client edited an outdated copy of the
 * resource (its If-Match version no longer matches).
 */
final class StaleVersion extends DomainException
{
    private const CODE = 'version_mismatch';

    public static function expected(int $expected, int $actual): self
    {
        return new self(sprintf(
            'The resource was modified by someone else (your version %d, current version %d). Reload it and try again.',
            $expected,
            $actual,
        ));
    }

    public function errorCode(): string
    {
        return self::CODE;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::PreconditionFailed;
    }
}
