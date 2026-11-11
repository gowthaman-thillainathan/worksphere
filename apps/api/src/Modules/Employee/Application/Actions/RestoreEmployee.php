<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeeInvariants;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAuditAction;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

/**
 * Brings a soft-deleted employee back. Idempotent for active employees.
 * Fails if another employee has taken the work e-mail address meanwhile.
 */
final readonly class RestoreEmployee
{
    public function __construct(
        private EmployeeRepository $employees,
        private EmployeeInvariants $invariants,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    public function handle(string $employeeId): Employee
    {
        $this->database->transaction(function () use ($employeeId): void {
            $employee = $this->employees->findForUpdate($employeeId, includingTrashed: true)
                ?? throw EmployeeNotFound::withId($employeeId);

            if (! $employee->trashed()) {
                return;
            }

            $this->invariants->assertWorkEmailAvailable($employee->work_email, $employee->id);

            $reason = $employee->deletion_reason;
            $employee->deletion_reason = null;
            $employee->version++;
            $this->employees->restore($employee);

            $this->audit->record(new AuditRecord(
                action: EmployeeAuditAction::Restored->value,
                subjectType: EmployeeAuditAction::SUBJECT_TYPE,
                subjectId: $employee->id,
                changes: $reason === null ? [] : [EmployeeAuditAction::DELETION_REASON_FIELD => [$reason, null]],
            ));
        });

        return $this->employees->find($employeeId) ?? throw EmployeeNotFound::withId($employeeId);
    }
}
