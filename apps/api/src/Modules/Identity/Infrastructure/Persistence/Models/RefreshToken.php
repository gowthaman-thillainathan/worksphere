<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Persistence\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One link in a refresh-token rotation chain. All tokens descending from one
 * login share a family_id, which is also the "sid" claim of access tokens.
 *
 * @property string $id
 * @property string $user_id
 * @property string $family_id
 * @property string $token_hash
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $revoked_at
 * @property string|null $replaced_by_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property-read User $user
 */
final class RefreshToken extends Model
{
    use HasUuids;
    use MassPrunable;

    protected $table = 'refresh_tokens';

    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'family_id', 'token_hash', 'expires_at', 'ip_address', 'user_agent'];

    /**
     * @var list<string>
     */
    protected $hidden = ['token_hash'];

    public function isUsableAt(DateTimeInterface $moment): bool
    {
        return $this->revoked_at === null && $this->expires_at->greaterThan($moment);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Expired tokens are kept for a while for incident investigation, then pruned.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $retentionDays = (int) app(Config::class)->get('identity.refresh_token.prune_after_days');

        return self::query()->where('expires_at', '<', CarbonImmutable::now()->subDays($retentionDays));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
