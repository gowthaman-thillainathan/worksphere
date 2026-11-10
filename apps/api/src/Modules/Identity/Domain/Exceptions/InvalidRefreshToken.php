<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

/**
 * Missing, unknown, expired, revoked or reused refresh token. The client must
 * sign in again.
 */
final class InvalidRefreshToken extends DomainException
{
    public function __construct()
    {
        parent::__construct('The session has expired. Please sign in again.');
    }

    public function errorCode(): string
    {
        return IdentityErrorCode::InvalidRefreshToken->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Unauthenticated;
    }
}
