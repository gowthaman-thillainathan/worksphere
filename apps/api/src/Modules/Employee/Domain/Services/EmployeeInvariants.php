<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Services;

use DateTimeInterface;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidDepartment;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidEmploymentDates;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidManager;
use Meridian\Modules\Employee\Domain\Exceptions\WorkEmailInUse;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Shared\Domain\Services\Hierarchy;

/**
 * Rules that span more than one employee and must hold for every write path.
 */
final readonly class EmployeeInvariants
{
    private const NO_DEPARTMENT = '';

    public function __construct(
        private EmployeeRepository $employees,
        private DepartmentLookup $departments,
    ) {}

    /**
     * The name to store with the employee for sorting: empty when there is
     * no department.
     *
     * @throws InvalidDepartment
     */
    public function departmentNameFor(?string $departmentId): string
    {
        if ($departmentId === null) {
            return self::NO_DEPARTMENT;
        }

        return $this->departments->nameOf($departmentId) ?? throw new InvalidDepartment;
    }

    /**
     * @throws WorkEmailInUse
     */
    public function assertWorkEmailAvailable(string $workEmail, ?string $employeeId = null): void
    {
        if ($this->employees->workEmailInUse($workEmail, $employeeId)) {
            throw new WorkEmailInUse;
        }
    }

    /**
     * A manager must exist, must not be the employee and must not report
     * (directly or indirectly) to the employee.
     *
     * @throws InvalidManager
     */
    public function assertValidManager(?string $employeeId, ?string $managerId): void
    {
        if ($managerId === null) {
            return;
        }

        if ($managerId === $employeeId) {
            throw InvalidManager::selfReporting();
        }

        if ($this->employees->find($managerId) === null) {
            throw InvalidManager::notFound();
        }

        if ($employeeId !== null && Hierarchy::wouldCreateCycle($employeeId, $managerId, $this->employees->managerIdOf(...))) {
            throw InvalidManager::cycle();
        }
    }

    /**
     * @throws InvalidEmploymentDates
     */
    public static function assertValidDates(EmployeeStatus $status, DateTimeInterface $hireDate, ?DateTimeInterface $terminationDate): void
    {
        if ($status->requiresTerminationDate() && $terminationDate === null) {
            throw InvalidEmploymentDates::terminationDateRequired();
        }

        if ($terminationDate !== null && $terminationDate < $hireDate) {
            throw InvalidEmploymentDates::terminationBeforeHire();
        }
    }
}
