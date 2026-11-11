<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use Illuminate\Http\Request;
use Meridian\Modules\Access\Contracts\Permission;

/**
 * Creating an account and choosing its role needs both user.manage and
 * access.manage: the same rule for every route that does it.
 */
final class AuthorizesAccountManagement
{
    /**
     * @var list<Permission>
     */
    public const PERMISSIONS = [Permission::UserManage, Permission::AccessManage];

    public static function allows(Request $request): bool
    {
        $user = $request->user();

        foreach (self::PERMISSIONS as $permission) {
            if (! $user?->can($permission->value)) {
                return false;
            }
        }

        return true;
    }
}
