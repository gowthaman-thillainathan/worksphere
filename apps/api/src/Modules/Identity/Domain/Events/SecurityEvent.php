<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

/**
 * An authentication event worth recording in the security log and, later,
 * the audit trail. Context must contain identifiers only, never PII values.
 */
interface SecurityEvent
{
    public function name(): string;

    public function isSuspicious(): bool;

    /**
     * @return array<string, string|int|bool|null>
     */
    public function context(): array;
}
