<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Exceptions;

enum EmployeeErrorCode: string
{
    case NotFound = 'employee_not_found';
    case WorkEmailInUse = 'work_email_in_use';
    case InvalidManager = 'invalid_manager';
    case HasDirectReports = 'employee_has_direct_reports';
    case PhotoNotFound = 'employee_photo_not_found';
    case InvalidEmploymentDates = 'invalid_employment_dates';
}
