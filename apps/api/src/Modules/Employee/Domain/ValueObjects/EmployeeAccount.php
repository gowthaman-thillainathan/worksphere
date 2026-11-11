<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

use DateTimeImmutable;

/**
 * The employee's sign-in account as the Employee module sees it.
 */
final readonly class EmployeeAccount
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $status,
        public bool $emailVerified,
        public ?string $role,
        public ?DateTimeImmutable $lastLoginAt,
        public DateTimeImmutable $createdAt,
    ) {}
}
