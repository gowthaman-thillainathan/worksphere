<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class InvalidPasswordResetToken extends DomainException
{
    public function __construct()
    {
        parent::__construct('This password reset link is invalid or has expired.');
    }

    public function errorCode(): string
    {
        return IdentityErrorCode::InvalidPasswordResetToken->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::InvalidInput;
    }
}
