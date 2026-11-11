<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class AccountAlreadyExists extends DomainException
{
    public function __construct()
    {
        parent::__construct('This employee already has a sign-in account.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::AccountAlreadyExists->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Conflict;
    }
}
