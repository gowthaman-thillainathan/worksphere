<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Data;

/**
 * Employee number format, built from config/employee.php.
 */
final readonly class EmployeeNumberPolicy
{
    public function __construct(
        public string $prefix,
        public int $padding,
    ) {}
}
