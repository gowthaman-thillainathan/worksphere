<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;
use Throwable;

final class InvalidAccessToken extends DomainException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('The access token is invalid: %s.', $reason), previous: $previous);
    }

    public function errorCode(): string
    {
        return IdentityErrorCode::InvalidAccessToken->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Unauthenticated;
    }
}
