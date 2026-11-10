<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

/**
 * Sortable fields. Every one is NOT NULL and indexed, which cursor
 * pagination requires.
 */
enum EmployeeSortField: string
{
    case EmployeeNumber = 'employee_number';
    case FirstName = 'first_name';
    case LastName = 'last_name';
    case HireDate = 'hire_date';
    case CreatedAt = 'created_at';
}
