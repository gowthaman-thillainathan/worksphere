<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Contracts;

/**
 * Read access to departments for other modules.
 */
interface DepartmentDirectory
{
    public function exists(string $departmentId): bool;

    /**
     * The department's current name, or null when it does not exist.
     */
    public function nameOf(string $departmentId): ?string;
}
