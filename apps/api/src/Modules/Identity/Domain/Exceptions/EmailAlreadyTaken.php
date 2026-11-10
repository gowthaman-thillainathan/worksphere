<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class EmailAlreadyTaken extends DomainException
{
    public function __construct()
    {
        parent::__construct('An account with this e-mail address already exists.');
    }

    public function errorCode(): string
    {
        return IdentityErrorCode::EmailAlreadyTaken->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Conflict;
    }
}
