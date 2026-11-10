<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property bool $is_system
 * @property-read Collection<int, PermissionRecord> $permissions
 */
final class Role extends Model
{
    use HasUuids;

    protected $table = 'roles';

    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'name', 'description', 'is_system'];

    /**
     * @return BelongsToMany<PermissionRecord, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionRecord::class, 'role_permissions', 'role_id', 'permission_id');
    }

    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
