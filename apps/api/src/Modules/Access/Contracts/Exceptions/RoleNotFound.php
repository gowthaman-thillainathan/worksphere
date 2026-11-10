<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Contracts\Exceptions;

use Meridian\Modules\Access\Domain\Exceptions\AccessErrorCode;
use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

final class RoleNotFound extends DomainException
{
    public static function withKey(string $roleKey): self
    {
        return new self(sprintf('Role "%s" does not exist.', $roleKey));
    }

    public function errorCode(): string
    {
        return AccessErrorCode::RoleNotFound->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::NotFound;
    }
}
