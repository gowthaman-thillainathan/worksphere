<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Repositories;

use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeSearchCriteria;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

interface EmployeeRepository
{
    /**
     * Active (not soft-deleted) employee with the relations the API shows.
     */
    public function find(string $id): ?Employee;

    /**
     * Locks the row until the surrounding transaction ends.
     */
    public function findForUpdate(string $id, bool $includingTrashed = false): ?Employee;

    /**
     * @return CursorPage<Employee>
     */
    public function search(EmployeeSearchCriteria $criteria): CursorPage;

    public function save(Employee $employee): void;

    /**
     * Persists pending changes (e.g. the deletion reason) and soft-deletes.
     */
    public function softDelete(Employee $employee): void;

    public function restore(Employee $employee): void;

    public function workEmailInUse(string $workEmail, ?string $exceptEmployeeId = null): bool;

    public function managerIdOf(string $employeeId): ?string;

    public function hasActiveDirectReports(string $employeeId): bool;

    /**
     * Active (not deleted) employees per status, every status included,
     * e.g. ['active' => 41, 'on_leave' => 3, ...].
     *
     * @return array<string, int>
     */
    public function countByStatus(): array;

    /**
     * Atomically reserves the next employee number sequence value.
     */
    public function nextSequenceValue(): int;

    /**
     * Refreshes the department name copied onto employees (deleted ones
     * included). Does not bump versions: the employee record itself did not
     * change.
     *
     * @return int Number of employees updated.
     */
    public function renameDepartment(string $departmentId, string $name): int;
}
