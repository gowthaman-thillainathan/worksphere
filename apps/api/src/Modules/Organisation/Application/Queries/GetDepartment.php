<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Application\Queries;

use Meridian\Modules\Organisation\Domain\Exceptions\DepartmentNotFound;
use Meridian\Modules\Organisation\Domain\Repositories\DepartmentRepository;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

final readonly class GetDepartment
{
    public function __construct(private DepartmentRepository $departments) {}

    public function handle(string $departmentId): Department
    {
        return $this->departments->find($departmentId) ?? throw DepartmentNotFound::withId($departmentId);
    }
}
