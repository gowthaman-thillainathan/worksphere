<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Infrastructure\Identity;

use Meridian\Modules\Access\Contracts\Exceptions\RoleNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\InvalidRole;
use Meridian\Modules\Employee\Domain\Services\EmployeeAccounts;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAccount;
use Meridian\Modules\Identity\Contracts\AccountProvisioner;
use Meridian\Modules\Identity\Contracts\Data\AccountSummary;

final readonly class IdentityEmployeeAccounts implements EmployeeAccounts
{
    public function __construct(private AccountProvisioner $accounts) {}

    public function invite(string $name, string $email, string $role, ?string $invitedBy): EmployeeAccount
    {
        try {
            return self::map($this->accounts->invite($name, $email, $role, $invitedBy));
        } catch (RoleNotFound) {
            throw new InvalidRole;
        }
    }

    public function changeRole(string $userId, string $role, ?string $changedBy): EmployeeAccount
    {
        try {
            return self::map($this->accounts->changeRole($userId, $role, $changedBy));
        } catch (RoleNotFound) {
            throw new InvalidRole;
        }
    }

    public function find(string $userId): ?EmployeeAccount
    {
        $account = $this->accounts->find($userId);

        return $account === null ? null : self::map($account);
    }

    private static function map(AccountSummary $account): EmployeeAccount
    {
        return new EmployeeAccount(
            userId: $account->id,
            email: $account->email,
            status: $account->status,
            emailVerified: $account->emailVerified,
            role: $account->role(),
            lastLoginAt: $account->lastLoginAt,
            createdAt: $account->createdAt,
        );
    }
}
