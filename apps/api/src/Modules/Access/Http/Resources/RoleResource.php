<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\PermissionRecord;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\Role;

/**
 * @mixin Role
 */
final class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'permissions' => $this->whenLoaded(
                'permissions',
                fn (): array => $this->permissions
                    ->map(static fn (PermissionRecord $permission): string => $permission->key)
                    ->values()
                    ->all(),
            ),
        ];
    }
}
