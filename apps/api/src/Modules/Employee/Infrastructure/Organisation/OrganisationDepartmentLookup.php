<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Infrastructure\Organisation;

use Meridian\Modules\Employee\Domain\Services\DepartmentLookup;
use Meridian\Modules\Organisation\Contracts\DepartmentDirectory;

final readonly class OrganisationDepartmentLookup implements DepartmentLookup
{
    public function __construct(private DepartmentDirectory $directory) {}

    public function exists(string $departmentId): bool
    {
        return $this->directory->exists($departmentId);
    }

    public function nameOf(string $departmentId): ?string
    {
        return $this->directory->nameOf($departmentId);
    }
}
