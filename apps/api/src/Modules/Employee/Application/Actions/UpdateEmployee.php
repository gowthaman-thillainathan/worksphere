<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Audit\Contracts\ChangeSet;
use Meridian\Modules\Employee\Application\Data\EmployeeChanges;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeeInvariants;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAuditAction;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Shared\Domain\Exceptions\StaleVersion;

/**
 * Partial update with optimistic locking: the client must prove it edited
 * the current version (If-Match). Only real changes bump the version and
 * produce an audit event.
 */
final readonly class UpdateEmployee
{
    public function __construct(
        private EmployeeRepository $employees,
        private EmployeeInvariants $invariants,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    public function handle(string $employeeId, EmployeeChanges $changes, int $expectedVersion): Employee
    {
        $this->database->transaction(function () use ($employeeId, $changes, $expectedVersion): void {
            $employee = $this->employees->findForUpdate($employeeId) ?? throw EmployeeNotFound::withId($employeeId);

            if ($employee->version !== $expectedVersion) {
                throw StaleVersion::expected($expectedVersion, $employee->version);
            }

            if ($changes->has('work_email')) {
                $this->invariants->assertWorkEmailAvailable((string) $changes->get('work_email'), $employee->id);
            }

            if ($changes->has('manager_id')) {
                $this->invariants->assertValidManager($employee->id, $changes->get('manager_id'));
            }

            if ($changes->has('department_id')) {
                $employee->department_name = $this->invariants->departmentNameFor($changes->get('department_id'));
            }

            $before = $employee->auditSnapshot();
            $employee->fill($changes->attributes);
            EmployeeInvariants::assertValidDates($employee->status, $employee->hire_date, $employee->termination_date);

            $diff = ChangeSet::between($before, $employee->auditSnapshot(), Employee::SENSITIVE_FIELDS);

            if ($diff === []) {
                return;
            }

            $employee->version++;
            $this->employees->save($employee);

            $this->audit->record(new AuditRecord(
                action: EmployeeAuditAction::Updated->value,
                subjectType: EmployeeAuditAction::SUBJECT_TYPE,
                subjectId: $employee->id,
                changes: $diff,
            ));
        });

        return $this->employees->find($employeeId) ?? throw EmployeeNotFound::withId($employeeId);
    }
}
