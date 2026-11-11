<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;

/**
 * @mixin AuditEvent
 */
final class AuditEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'audit_event',
            'occurred_at' => $this->occurred_at->toIso8601ZuluString(),
            'actor' => ['type' => $this->actor_type->value, 'id' => $this->actor_id, 'name' => $this->resource->actorName],
            'action' => $this->action,
            'subject' => ['type' => $this->subject_type, 'id' => $this->subject_id],
            'changes' => (object) $this->changes,
            'ip_address' => $this->ip_address,
            'request_id' => $this->request_id,
        ];
    }
}
