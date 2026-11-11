<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Listeners;

use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Organisation\Contracts\Events\DepartmentRenamed;

/**
 * Keeps the department name copied onto employees current. Runs inside the
 * renaming transaction (synchronous listener), so both commit together.
 */
final readonly class SyncDepartmentName
{
    public function __construct(private EmployeeRepository $employees) {}

    public function handle(DepartmentRenamed $event): void
    {
        $this->employees->renameDepartment($event->departmentId, $event->name);
    }
}
