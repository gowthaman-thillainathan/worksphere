<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Domain\Services;

/**
 * Port onto employees, adapted onto the Employee module's EmployeeDirectory.
 */
interface EmployeeLookup
{
    public function isActive(string $employeeId): bool;
}
