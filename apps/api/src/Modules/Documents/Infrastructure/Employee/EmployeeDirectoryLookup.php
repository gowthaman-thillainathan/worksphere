<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Infrastructure\Employee;

use Meridian\Modules\Documents\Domain\Services\EmployeeLookup;
use Meridian\Modules\Employee\Contracts\EmployeeDirectory;

final readonly class EmployeeDirectoryLookup implements EmployeeLookup
{
    public function __construct(private EmployeeDirectory $directory) {}

    public function isActive(string $employeeId): bool
    {
        return $this->directory->isActive($employeeId);
    }
}
