<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Infrastructure\Persistence\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grants a role to a user, optionally until a point in time.
 *
 * Scope columns (department, location...) are added by an expand migration
 * when scoped roles are introduced (docs/07 §5).
 *
 * @property string $id
 * @property string $user_id
 * @property string $role_id
 * @property string|null $granted_by
 * @property CarbonImmutable|null $expires_at
 */
final class RoleAssignment extends Model
{
    use HasUuids;

    protected $table = 'role_assignments';

    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'role_id', 'granted_by', 'expires_at'];

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Assignments that have not expired at the given moment.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActiveAt(Builder $query, DateTimeInterface $moment): void
    {
        $query->where(static function (Builder $query) use ($moment): void {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', $moment);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
        ];
    }
}
