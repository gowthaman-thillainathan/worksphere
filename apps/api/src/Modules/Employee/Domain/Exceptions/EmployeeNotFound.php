<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class EmployeeNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Employee %s was not found.', $id));
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::NotFound->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::NotFound;
    }
}
