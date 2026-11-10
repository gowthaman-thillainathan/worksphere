<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Database mirror of a {@see \Meridian\Modules\Access\Contracts\Permission}.
 *
 * @property string $id
 * @property string $key
 * @property string $module
 * @property string $description
 * @property bool $is_sensitive
 */
final class PermissionRecord extends Model
{
    use HasUuids;

    protected $table = 'permissions';

    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'module', 'description', 'is_sensitive'];

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
        ];
    }
}
