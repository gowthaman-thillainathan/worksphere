<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Queries;

use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

final readonly class GetEmployee
{
    public function __construct(private EmployeeRepository $employees) {}

    public function handle(string $employeeId): Employee
    {
        return $this->employees->find($employeeId) ?? throw EmployeeNotFound::withId($employeeId);
    }
}
