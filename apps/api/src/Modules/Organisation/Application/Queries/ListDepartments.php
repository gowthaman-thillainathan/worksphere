<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Application\Queries;

use Meridian\Modules\Organisation\Domain\Repositories\DepartmentRepository;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

final readonly class ListDepartments
{
    public function __construct(private DepartmentRepository $departments) {}

    /**
     * @return list<Department>
     */
    public function handle(): array
    {
        return $this->departments->all();
    }
}
