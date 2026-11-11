<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class AccountNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('This employee has no sign-in account.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::AccountNotFound->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::NotFound;
    }
}
