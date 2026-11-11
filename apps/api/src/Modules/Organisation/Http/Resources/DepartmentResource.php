<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

/**
 * @mixin Department
 */
final class DepartmentResource extends JsonResource
{
    /**
     * @var array<string, Permission>
     */
    private const RECORD_ACTIONS = [
        'update' => Permission::OrganisationManage,
        'delete' => Permission::OrganisationManage,
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'type' => 'department',
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'parent' => $this->whenLoaded('parent', fn (): ?array => $this->parent === null ? null : [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ]),
            'created_at' => $this->created_at->toIso8601ZuluString(),
            'updated_at' => $this->updated_at->toIso8601ZuluString(),
            'meta' => [
                'version' => $this->version,
                'permissions' => array_keys(array_filter(
                    self::RECORD_ACTIONS,
                    static fn (Permission $permission): bool => (bool) $user?->can($permission->value),
                )),
            ],
        ];
    }
}
