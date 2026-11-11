<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class InvalidRole extends DomainException
{
    public function __construct()
    {
        parent::__construct('The selected role does not exist.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::InvalidRole->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::InvalidInput;
    }
}
