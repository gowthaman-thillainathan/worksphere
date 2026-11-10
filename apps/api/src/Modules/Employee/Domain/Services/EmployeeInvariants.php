<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Services;

use DateTimeInterface;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidEmploymentDates;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidManager;
use Meridian\Modules\Employee\Domain\Exceptions\WorkEmailInUse;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;

/**
 * Rules that span more than one employee and must hold for every write path.
 */
final readonly class EmployeeInvariants
{
    /**
     * Guards against corrupted data turning the walk into an endless loop.
     */
    private const MAX_REPORTING_DEPTH = 100;

    public function __construct(private EmployeeRepository $employees) {}

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

        if ($employeeId !== null && $this->reportsTo($managerId, $employeeId)) {
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

    private function reportsTo(string $employeeId, string $possibleManagerId): bool
    {
        $current = $this->employees->managerIdOf($employeeId);

        for ($depth = 0; $current !== null && $depth < self::MAX_REPORTING_DEPTH; $depth++) {
            if ($current === $possibleManagerId) {
                return true;
            }

            $current = $this->employees->managerIdOf($current);
        }

        return $current !== null;
    }
}
