<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Domain\Exceptions;

use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;

/**
 * Also used for confidential documents the user may not see, so their
 * existence is not revealed.
 */
final class DocumentNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Document %s was not found.', $id));
    }

    public function errorCode(): string
    {
        return DocumentsErrorCode::DocumentNotFound->value;
    }

    public function category(): ErrorCategory
    {
        return ErrorCategory::NotFound;
    }
}
