<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class DepartmentNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Department %s was not found.', $id));
    }

    public function errorCode(): string
    {
        return OrganisationErrorCode::DepartmentNotFound->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::NotFound;
    }
}
