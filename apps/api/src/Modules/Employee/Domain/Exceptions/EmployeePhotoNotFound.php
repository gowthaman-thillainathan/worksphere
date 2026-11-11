<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class EmployeePhotoNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('This employee has no photo.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::PhotoNotFound->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::NotFound;
    }
}
