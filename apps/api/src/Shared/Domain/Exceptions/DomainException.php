<?php

declare(strict_types=1);

namespace Meridian\Shared\Domain\Exceptions;

use DomainException as PhpDomainException;

/**
 * Base class for expected business-rule failures.
 *
 * Messages are written for API consumers and are safe to expose. Unexpected
 * failures must not extend this class.
 */
abstract class DomainException extends PhpDomainException
{
    /**
     * Stable, machine-readable error code (snake_case) clients can switch on.
     */
    abstract public function errorCode(): string;

    abstract public function category(): ErrorCategory;
}
