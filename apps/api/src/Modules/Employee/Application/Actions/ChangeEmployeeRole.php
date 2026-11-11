<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Employee\Domain\Exceptions\AccountNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidRole;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeeAccounts;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAccount;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAuditAction;

/**
 * Replaces the role of the employee's account. Recorded on the employee's
 * history as employee.role_changed; a no-op when the role is unchanged.
 */
final readonly class ChangeEmployeeRole
{
    public function __construct(
        private EmployeeRepository $employees,
        private EmployeeAccounts $accounts,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    /**
     * @throws AccountNotFound
     * @throws InvalidRole
     */
    public function handle(string $employeeId, string $role, ?string $actorId): EmployeeAccount
    {
        return $this->database->transaction(function () use ($employeeId, $role, $actorId): EmployeeAccount {
            $employee = $this->employees->findForUpdate($employeeId) ?? throw EmployeeNotFound::withId($employeeId);
            $account = ($employee->user_id === null ? null : $this->accounts->find($employee->user_id)) ?? throw new AccountNotFound;

            if ($account->role === $role) {
                return $account;
            }

            $updated = $this->accounts->changeRole($account->userId, $role, $actorId);

            $this->audit->record(new AuditRecord(
                action: EmployeeAuditAction::RoleChanged->value,
                subjectType: EmployeeAuditAction::SUBJECT_TYPE,
                subjectId: $employee->id,
                changes: [EmployeeAuditAction::ROLE_FIELD => [$account->role, $role]],
            ));

            return $updated;
        });
    }
}
