<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Services;

/**
 * Port onto departments. Adapted onto the Organisation module's public
 * DepartmentDirectory contract in the infrastructure layer, so the Employee
 * domain stays independent of other modules.
 */
interface DepartmentLookup
{
    public function exists(string $departmentId): bool;

    /**
     * The department's current name, or null when it does not exist.
     */
    public function nameOf(string $departmentId): ?string;
}
