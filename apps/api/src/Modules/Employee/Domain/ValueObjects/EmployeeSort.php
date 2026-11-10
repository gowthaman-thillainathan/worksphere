<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

use Meridian\Shared\Domain\ValueObjects\SortDirection;

final readonly class EmployeeSort
{
    public function __construct(
        public EmployeeSortField $field,
        public SortDirection $direction,
    ) {}
}
