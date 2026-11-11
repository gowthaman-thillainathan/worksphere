<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Services;

/**
 * Port: does a department exist? Adapted onto the Organisation module's
 * public DepartmentDirectory contract in the infrastructure layer, so the
 * Employee domain stays independent of other modules.
 */
interface DepartmentLookup
{
    public function exists(string $departmentId): bool;
}
