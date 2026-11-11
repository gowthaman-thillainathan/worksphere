<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class DepartmentInUse extends DomainException
{
    public static function hasSubDepartments(): self
    {
        return new self('Move or delete the sub-departments first.');
    }

    public static function hasMembers(): self
    {
        return new self('Reassign the employees of this department first.');
    }

    public function errorCode(): string
    {
        return OrganisationErrorCode::DepartmentInUse->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Conflict;
    }
}
