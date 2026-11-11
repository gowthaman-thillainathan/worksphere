<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

/**
 * Lifecycle state of an employee (docs/03 §2.5).
 */
enum EmployeeStatus: string
{
    case PreBoarding = 'pre_boarding';
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Notice = 'notice';
    case Terminated = 'terminated';

    public function requiresTerminationDate(): bool
    {
        return $this === self::Terminated;
    }
}
