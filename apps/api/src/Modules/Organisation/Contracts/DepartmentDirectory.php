<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Contracts;

/**
 * Read access to departments for other modules.
 */
interface DepartmentDirectory
{
    public function exists(string $departmentId): bool;
}
