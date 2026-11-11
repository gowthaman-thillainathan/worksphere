<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Domain\Exceptions;

enum OrganisationErrorCode: string
{
    case DepartmentNotFound = 'department_not_found';
    case DepartmentCodeInUse = 'department_code_in_use';
    case InvalidParentDepartment = 'invalid_parent_department';
    case DepartmentInUse = 'department_in_use';
}
