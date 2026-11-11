<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\Services;

use Meridian\Modules\Employee\Domain\Exceptions\InvalidRole;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAccount;

/**
 * Port: sign-in accounts for employees. Adapted onto the Identity module's
 * AccountProvisioner contract (ADR-0007).
 */
interface EmployeeAccounts
{
    /**
     * Creates the account and sends the person an invitation to set a password.
     *
     * @throws InvalidRole
     */
    public function invite(string $name, string $email, string $role, ?string $invitedBy): EmployeeAccount;

    /**
     * @throws InvalidRole
     */
    public function changeRole(string $userId, string $role, ?string $changedBy): EmployeeAccount;

    public function find(string $userId): ?EmployeeAccount;
}
