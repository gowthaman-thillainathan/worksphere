<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

/**
 * Brute-force protection settings, built from config/identity.php.
 */
final readonly class LockoutPolicy
{
    public function __construct(
        public int $maxAttempts,
        public int $durationSeconds,
    ) {}
}
