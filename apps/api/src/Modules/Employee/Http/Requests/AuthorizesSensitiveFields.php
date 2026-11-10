<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use Illuminate\Http\Request;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

/**
 * Writing personal data needs employee.update.sensitive. Requests that try
 * without it are rejected (403), never silently stripped (docs/07 §6).
 */
final class AuthorizesSensitiveFields
{
    public static function allows(Request $request): bool
    {
        if (! $request->hasAny(Employee::SENSITIVE_FIELDS)) {
            return true;
        }

        return (bool) $request->user()?->can(Permission::EmployeeUpdateSensitive->value);
    }
}
