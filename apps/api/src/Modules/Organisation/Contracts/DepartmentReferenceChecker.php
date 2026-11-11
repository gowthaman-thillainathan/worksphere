<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Contracts;

/**
 * Implemented by modules whose records point at departments (e.g. Employee),
 * so a department that is still in use cannot be deleted. Organisation owns
 * the interface; other modules register implementations under TAG, which
 * keeps Organisation free of dependencies on them.
 */
interface DepartmentReferenceChecker
{
    public const TAG = 'organisation.department-reference-checkers';

    public function isReferenced(string $departmentId): bool;
}
