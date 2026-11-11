<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Domain\Repositories;

use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

interface DepartmentRepository
{
    public function find(string $id): ?Department;

    /**
     * Locks the row until the surrounding transaction ends.
     */
    public function findForUpdate(string $id): ?Department;

    /**
     * Every department ordered by name, with its parent loaded. Departments
     * are reference data (tens to hundreds of rows), so no pagination.
     *
     * @return list<Department>
     */
    public function all(): array;

    public function save(Department $department): void;

    public function delete(Department $department): void;

    public function codeInUse(string $code, ?string $exceptId = null): bool;

    public function parentIdOf(string $id): ?string;

    public function hasChildren(string $id): bool;
}
