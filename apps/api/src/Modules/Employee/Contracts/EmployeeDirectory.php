<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Contracts;

/**
 * Read access to employees for other modules (e.g. Documents).
 */
interface EmployeeDirectory
{
    /**
     * True for employees that exist and are not deleted.
     */
    public function isActive(string $employeeId): bool;
}
