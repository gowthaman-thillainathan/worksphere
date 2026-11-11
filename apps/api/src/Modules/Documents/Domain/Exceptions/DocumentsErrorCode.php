<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Domain\Exceptions;

enum DocumentsErrorCode: string
{
    case DocumentNotFound = 'document_not_found';
    case EmployeeNotFound = 'document_employee_not_found';
}
