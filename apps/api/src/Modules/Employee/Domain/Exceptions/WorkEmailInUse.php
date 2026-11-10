<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class WorkEmailInUse extends DomainException
{
    public function __construct()
    {
        parent::__construct('Another employee already uses this work e-mail address.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::WorkEmailInUse->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Conflict;
    }
}
