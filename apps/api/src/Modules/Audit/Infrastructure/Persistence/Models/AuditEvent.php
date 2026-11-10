<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Infrastructure\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Meridian\Modules\Audit\Domain\ValueObjects\ActorType;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventData;

/**
 * Append-only: the model refuses updates and deletes. (At the database level
 * the application role should only hold INSERT/SELECT on this table.)
 *
 * @property int $id
 * @property CarbonImmutable $occurred_at
 * @property ActorType $actor_type
 * @property string|null $actor_id
 * @property string $action
 * @property string $subject_type
 * @property string $subject_id
 * @property array<string, mixed> $changes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $request_id
 * @property string|null $prev_hash
 * @property string $hash
 */
final class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = null;

    protected $table = 'audit_events';

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    public function toData(): AuditEventData
    {
        return new AuditEventData(
            occurredAt: $this->occurred_at,
            actorType: $this->actor_type,
            actorId: $this->actor_id,
            action: $this->action,
            subjectType: $this->subject_type,
            subjectId: $this->subject_id,
            // Read via getAttribute: Eloquent has its own protected $changes property.
            changes: $this->getAttribute('changes'),
            ipAddress: $this->ip_address,
            userAgent: $this->user_agent,
            requestId: $this->request_id,
        );
    }

    protected static function booted(): void
    {
        $refuse = static fn (): never => throw new LogicException('Audit events are immutable.');

        self::updating($refuse);
        self::deleting($refuse);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'actor_type' => ActorType::class,
            'changes' => 'array',
        ];
    }
}
