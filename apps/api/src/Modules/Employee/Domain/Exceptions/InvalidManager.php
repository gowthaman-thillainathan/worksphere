<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class InvalidManager extends DomainException
{
    public static function notFound(): self
    {
        return new self('The selected manager does not exist.');
    }

    public static function selfReporting(): self
    {
        return new self('An employee cannot be their own manager.');
    }

    public static function cycle(): self
    {
        return new self('This manager would create a reporting cycle.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::InvalidManager->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::InvalidInput;
    }
}
