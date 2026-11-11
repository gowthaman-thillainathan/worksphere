<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Contracts\Events;

/**
 * Published inside the renaming transaction, so modules that keep a copy of
 * the name (e.g. Employee, for sorting) update it atomically.
 */
final readonly class DepartmentRenamed
{
    public function __construct(
        public string $departmentId,
        public string $name,
    ) {}
}
