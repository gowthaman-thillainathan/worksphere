<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Domain\Services;

use Meridian\Modules\Organisation\Domain\Exceptions\DepartmentCodeInUse;
use Meridian\Modules\Organisation\Domain\Exceptions\InvalidParentDepartment;
use Meridian\Modules\Organisation\Domain\Repositories\DepartmentRepository;
use Meridian\Shared\Domain\Services\Hierarchy;

final readonly class DepartmentInvariants
{
    public function __construct(private DepartmentRepository $departments) {}

    /**
     * @throws DepartmentCodeInUse
     */
    public function assertCodeAvailable(string $code, ?string $departmentId = null): void
    {
        if ($this->departments->codeInUse($code, $departmentId)) {
            throw new DepartmentCodeInUse;
        }
    }

    /**
     * @throws InvalidParentDepartment
     */
    public function assertValidParent(?string $departmentId, ?string $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $departmentId) {
            throw InvalidParentDepartment::cycle();
        }

        if ($this->departments->find($parentId) === null) {
            throw InvalidParentDepartment::notFound();
        }

        if ($departmentId !== null && Hierarchy::wouldCreateCycle($departmentId, $parentId, $this->departments->parentIdOf(...))) {
            throw InvalidParentDepartment::cycle();
        }
    }
}
