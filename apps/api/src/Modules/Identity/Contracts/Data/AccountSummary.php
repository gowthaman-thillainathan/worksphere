<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Contracts\Data;

use DateTimeImmutable;

/**
 * What other modules may know about an account. No credentials.
 */
final readonly class AccountSummary
{
    /**
     * @param  list<string>  $roles  Role keys
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $status,
        public bool $emailVerified,
        public array $roles,
        public ?DateTimeImmutable $lastLoginAt,
        public DateTimeImmutable $createdAt,
    ) {}

    /**
     * The single role the account holds, or null if it has none.
     */
    public function role(): ?string
    {
        return $this->roles[0] ?? null;
    }
}
