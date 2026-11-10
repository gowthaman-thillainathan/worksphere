<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Repositories;

use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

interface UserRepository
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function emailExists(string $email): bool;

    public function save(User $user): void;

    /**
     * Atomically increments the failed-login counter.
     *
     * @return int The counter value after the increment.
     */
    public function registerFailedLogin(User $user): int;
}
