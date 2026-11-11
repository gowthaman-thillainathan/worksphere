<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Infrastructure\Organisation;

use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Modules\Organisation\Contracts\DepartmentReferenceChecker;

/**
 * Tells the Organisation module a department still has employees, including
 * soft-deleted ones (they can be restored into it).
 */
final class EmployeeDepartmentReferenceChecker implements DepartmentReferenceChecker
{
    public function isReferenced(string $departmentId): bool
    {
        return Employee::query()->withTrashed()->where('department_id', $departmentId)->exists();
    }
}
