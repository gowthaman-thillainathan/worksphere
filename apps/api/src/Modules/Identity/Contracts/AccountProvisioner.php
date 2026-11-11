<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Contracts;

use Meridian\Modules\Access\Contracts\Exceptions\RoleNotFound;
use Meridian\Modules\Identity\Contracts\Data\AccountSummary;
use Meridian\Shared\Domain\Exceptions\DomainException;

/**
 * Sign-in accounts for other modules, e.g. Employee giving an employee
 * access to Meridian (ADR-0007). Each account holds exactly one role here.
 */
interface AccountProvisioner
{
    /**
     * Creates an account without a password and e-mails an invitation to
     * set one (after the surrounding transaction commits). Setting the
     * password also verifies the e-mail address.
     *
     * @throws RoleNotFound
     * @throws DomainException When the e-mail address already has an account (409).
     */
    public function invite(string $name, string $email, string $roleKey, ?string $invitedBy = null): AccountSummary;

    /**
     * @throws RoleNotFound
     */
    public function changeRole(string $userId, string $roleKey, ?string $changedBy = null): AccountSummary;

    public function find(string $userId): ?AccountSummary;
}
