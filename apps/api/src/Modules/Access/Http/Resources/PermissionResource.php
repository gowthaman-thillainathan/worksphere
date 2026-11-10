<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Modules\Access\Infrastructure\Persistence\Models\PermissionRecord;

/**
 * @mixin PermissionRecord
 */
final class PermissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'module' => $this->module,
            'description' => $this->description,
            'is_sensitive' => $this->is_sensitive,
        ];
    }
}
