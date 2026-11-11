<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class DocumentEmployeeNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Employee %s was not found.', $id));
    }

    public function errorCode(): string
    {
        return DocumentsErrorCode::EmployeeNotFound->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::NotFound;
    }
}
