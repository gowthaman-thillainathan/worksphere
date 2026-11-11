<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\HasDirectReports;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAuditAction;
use Meridian\Shared\Domain\Exceptions\StaleVersion;

/**
 * Soft delete: the record leaves the directory but stays restorable and
 * keeps its audit history. A reason is required and stored with the record
 * and in the audit trail. Employees who still manage others cannot be
 * deleted, so no one is left reporting to a removed manager.
 */
final readonly class DeleteEmployee
{
    public function __construct(
        private EmployeeRepository $employees,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    public function handle(string $employeeId, int $expectedVersion, string $reason): void
    {
        $this->database->transaction(function () use ($employeeId, $expectedVersion, $reason): void {
            $employee = $this->employees->findForUpdate($employeeId) ?? throw EmployeeNotFound::withId($employeeId);

            if ($employee->version !== $expectedVersion) {
                throw StaleVersion::expected($expectedVersion, $employee->version);
            }

            if ($this->employees->hasActiveDirectReports($employee->id)) {
                throw new HasDirectReports;
            }

            $employee->deletion_reason = $reason;
            $employee->version++;
            $this->employees->softDelete($employee);

            $this->audit->record(new AuditRecord(
                action: EmployeeAuditAction::Deleted->value,
                subjectType: EmployeeAuditAction::SUBJECT_TYPE,
                subjectId: $employee->id,
                changes: [EmployeeAuditAction::DELETION_REASON_FIELD => [null, $reason]],
            ));
        });
    }
}
