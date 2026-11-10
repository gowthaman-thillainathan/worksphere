<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

/**
 * Deliberately identical for unknown e-mail, wrong password, locked and
 * disabled accounts, so responses cannot be used to enumerate users.
 */
final class InvalidCredentials extends DomainException
{
    public function __construct()
    {
        parent::__construct('The provided credentials are incorrect.');
    }

    public function errorCode(): string
    {
        return IdentityErrorCode::InvalidCredentials->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Unauthenticated;
    }
}
