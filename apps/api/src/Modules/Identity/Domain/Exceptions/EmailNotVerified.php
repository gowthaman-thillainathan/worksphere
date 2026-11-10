<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class EmailNotVerified extends DomainException
{
    public function __construct()
    {
        parent::__construct('Verify your e-mail address to continue.');
    }

    public function errorCode(): string
    {
        return IdentityErrorCode::EmailNotVerified->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Forbidden;
    }
}
