<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class HasDirectReports extends DomainException
{
    public function __construct()
    {
        parent::__construct('Reassign this employee\'s direct reports before deleting them.');
    }

    public function errorCode(): string
    {
        return EmployeeErrorCode::HasDirectReports->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::Conflict;
    }
}
