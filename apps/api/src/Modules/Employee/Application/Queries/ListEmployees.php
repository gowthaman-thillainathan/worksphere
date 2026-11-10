<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Queries;

use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeSearchCriteria;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

final readonly class ListEmployees
{
    public function __construct(private EmployeeRepository $employees) {}

    /**
     * @return CursorPage<Employee>
     */
    public function handle(EmployeeSearchCriteria $criteria): CursorPage
    {
        return $this->employees->search($criteria);
    }
}
