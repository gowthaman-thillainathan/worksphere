<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Employee\Domain\Exceptions\AccountAlreadyExists;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidRole;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeeAccounts;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAccount;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAuditAction;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

/**
 * Gives an employee access to Meridian: an account on their work e-mail
 * address with one role, and an invitation to set a password.
 */
final readonly class CreateEmployeeAccount
{
    public function __construct(
        private EmployeeRepository $employees,
        private EmployeeAccounts $accounts,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    /**
     * @throws AccountAlreadyExists
     * @throws InvalidRole
     */
    public function handle(string $employeeId, string $role, ?string $actorId): EmployeeAccount
    {
        return $this->database->transaction(function () use ($employeeId, $role, $actorId): EmployeeAccount {
            $employee = $this->employees->findForUpdate($employeeId) ?? throw EmployeeNotFound::withId($employeeId);

            return $this->provision($employee, $role, $actorId);
        });
    }

    /**
     * Shared with CreateEmployee; the caller holds the transaction and the row.
     *
     * @throws AccountAlreadyExists
     * @throws InvalidRole
     */
    public function provision(Employee $employee, string $role, ?string $actorId): EmployeeAccount
    {
        if ($employee->user_id !== null) {
            throw new AccountAlreadyExists;
        }

        $account = $this->accounts->invite($employee->displayName(), $employee->work_email, $role, $actorId);

        $employee->user_id = $account->userId;
        $employee->version++;
        $this->employees->save($employee);

        $this->audit->record(new AuditRecord(
            action: EmployeeAuditAction::AccountCreated->value,
            subjectType: EmployeeAuditAction::SUBJECT_TYPE,
            subjectId: $employee->id,
            changes: [EmployeeAuditAction::ROLE_FIELD => [null, $role]],
        ));

        return $account;
    }
}
