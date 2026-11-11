<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Persistence\Repositories;

use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

final class EloquentUserRepository implements UserRepository
{
    private const FAILED_LOGIN_COUNTER = 'failed_login_count';

    public function findById(string $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $this->normalise($email))->first();
    }

    public function emailExists(string $email): bool
    {
        return User::query()->where('email', $this->normalise($email))->exists();
    }

    public function save(User $user): void
    {
        $user->save();
    }

    public function registerFailedLogin(User $user): int
    {
        $user->increment(self::FAILED_LOGIN_COUNTER);

        return $user->failed_login_count;
    }

    private function normalise(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function namesByIds(array $ids): array
    {
        /** @var array<string, string> */
        return User::query()->whereKey($ids)->pluck('name', 'id')->all();
    }
}
