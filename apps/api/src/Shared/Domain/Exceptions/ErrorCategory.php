<?php

declare(strict_types=1);

namespace Meridian\Shared\Domain\Exceptions;

/**
 * Business classification of a domain failure.
 *
 * The domain says *what kind* of failure happened; the HTTP layer decides
 * which status code represents it.
 */
enum ErrorCategory
{
    case InvalidInput;
    case Unauthenticated;
    case Forbidden;
    case NotFound;
    case Conflict;
    case PreconditionFailed;
}
