<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Queries;

use Meridian\Modules\Employee\Domain\Exceptions\AccountNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeeAccounts;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAccount;

final readonly class GetEmployeeAccount
{
    public function __construct(
        private EmployeeRepository $employees,
        private EmployeeAccounts $accounts,
    ) {}

    /**
     * @throws AccountNotFound
     */
    public function handle(string $employeeId): EmployeeAccount
    {
        $employee = $this->employees->find($employeeId) ?? throw EmployeeNotFound::withId($employeeId);

        return ($employee->user_id === null ? null : $this->accounts->find($employee->user_id)) ?? throw new AccountNotFound;
    }
}
