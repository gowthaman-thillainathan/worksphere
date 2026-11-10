<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class InvalidEmploymentDates extends DomainException
{
    public static function terminationDateRequired(): self
    {
        return new self('A termination date is required when the status is "terminated".');
    }

    public static function terminationBeforeHire(): self
    {
        return new self('The termination date cannot be before the hire date.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::InvalidEmploymentDates->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::InvalidInput;
    }
}
