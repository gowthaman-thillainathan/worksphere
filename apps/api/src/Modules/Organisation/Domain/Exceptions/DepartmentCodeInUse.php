<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class DepartmentCodeInUse extends DomainException
{
    public function __construct()
    {
        parent::__construct('Another department already uses this code.');
    }

    public function errorCode(): string
    {
        return OrganisationErrorCode::DepartmentCodeInUse->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Conflict;
    }
}
