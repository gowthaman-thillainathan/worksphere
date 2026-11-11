<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class InvalidParentDepartment extends DomainException
{
    public static function notFound(): self
    {
        return new self('The selected parent department does not exist.');
    }

    public static function cycle(): self
    {
        return new self('A department cannot be placed under itself or one of its sub-departments.');
    }

    public function errorCode(): string
    {
        return OrganisationErrorCode::InvalidParentDepartment->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::InvalidInput;
    }
}
