<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

enum EmployeeAuditAction: string
{
    public const SUBJECT_TYPE = 'employee';

    case Created = 'employee.created';
    case Updated = 'employee.updated';
    case Deleted = 'employee.deleted';
    case Restored = 'employee.restored';
    case PhotoUpdated = 'employee.photo_updated';
    case PhotoRemoved = 'employee.photo_removed';
}
