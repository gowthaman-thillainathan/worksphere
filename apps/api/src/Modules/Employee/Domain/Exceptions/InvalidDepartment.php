<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class InvalidDepartment extends DomainException
{
    public function __construct()
    {
        parent::__construct('The selected department does not exist.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::InvalidDepartment->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::InvalidInput;
    }
}
