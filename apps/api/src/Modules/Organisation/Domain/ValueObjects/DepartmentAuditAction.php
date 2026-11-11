<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Domain\ValueObjects;

enum DepartmentAuditAction: string
{
    public const SUBJECT_TYPE = 'department';

    case Created = 'department.created';
    case Updated = 'department.updated';
    case Deleted = 'department.deleted';
}
