<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Audit\Contracts\ChangeSet;
use Meridian\Modules\Employee\Application\Data\EmployeeNumberPolicy;
use Meridian\Modules\Employee\Application\Data\NewEmployeeData;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidEmploymentDates;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidManager;
use Meridian\Modules\Employee\Domain\Exceptions\WorkEmailInUse;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeeInvariants;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAuditAction;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeNumber;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

/**
 * Hires an employee: assigns the next employee number and records the
 * creation in the audit trail in the same transaction.
 */
final readonly class CreateEmployee
{
    public function __construct(
        private EmployeeRepository $employees,
        private EmployeeInvariants $invariants,
        private AuditRecorder $audit,
        private EmployeeNumberPolicy $numbering,
        private ConnectionInterface $database,
    ) {}

    /**
     * @throws WorkEmailInUse
     * @throws InvalidManager
     * @throws InvalidEmploymentDates
     */
    public function handle(NewEmployeeData $data): Employee
    {
        EmployeeInvariants::assertValidDates($data->status, $data->hireDate, $data->terminationDate);

        $employeeId = $this->database->transaction(function () use ($data): string {
            $this->invariants->assertWorkEmailAvailable($data->workEmail);
            $this->invariants->assertValidManager(null, $data->managerId);

            $employee = new Employee($data->toAttributes());
            $employee->employee_number = EmployeeNumber::fromSequence(
                $this->employees->nextSequenceValue(),
                $this->numbering->prefix,
                $this->numbering->padding,
            )->value;
            $this->employees->save($employee);

            $this->audit->record(new AuditRecord(
                action: EmployeeAuditAction::Created->value,
                subjectType: EmployeeAuditAction::SUBJECT_TYPE,
                subjectId: $employee->id,
                changes: ChangeSet::created(
                    ['employee_number' => $employee->employee_number, ...$employee->auditSnapshot()],
                    Employee::SENSITIVE_FIELDS,
                ),
            ));

            return $employee->id;
        });

        return $this->employees->find($employeeId) ?? throw EmployeeNotFound::withId($employeeId);
    }
}
