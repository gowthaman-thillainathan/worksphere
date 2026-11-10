<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use Meridian\Modules\Identity\Domain\Repositories\RefreshTokenRepository;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\RefreshToken;

final class EloquentRefreshTokenRepository implements RefreshTokenRepository
{
    public function create(
        string $userId,
        string $familyId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        ?string $ipAddress,
        ?string $userAgent,
    ): RefreshToken {
        return RefreshToken::query()->create([
            'user_id' => $userId,
            'family_id' => $familyId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function findByHash(string $tokenHash): ?RefreshToken
    {
        return RefreshToken::query()->where('token_hash', $tokenHash)->first();
    }

    public function findByHashForUpdate(string $tokenHash): ?RefreshToken
    {
        return RefreshToken::query()->where('token_hash', $tokenHash)->lockForUpdate()->first();
    }

    public function markReplaced(RefreshToken $token, string $replacementId, DateTimeImmutable $at): void
    {
        $token->forceFill(['revoked_at' => $at, 'replaced_by_id' => $replacementId])->save();
    }

    public function revokeFamily(string $familyId, DateTimeImmutable $at): int
    {
        return RefreshToken::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $at]);
    }

    public function revokeAllForUser(string $userId, DateTimeImmutable $at): int
    {
        return RefreshToken::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $at]);
    }
}
