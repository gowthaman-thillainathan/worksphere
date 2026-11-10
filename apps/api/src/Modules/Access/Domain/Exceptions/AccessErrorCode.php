<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Domain\Exceptions;

enum AccessErrorCode: string
{
    case RoleNotFound = 'role_not_found';
}
