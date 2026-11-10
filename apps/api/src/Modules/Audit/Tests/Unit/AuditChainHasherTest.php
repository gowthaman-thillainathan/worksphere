<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Meridian\Modules\Audit\Domain\Services\AuditChainHasher;
use Meridian\Modules\Audit\Domain\ValueObjects\ActorType;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventData;

function auditEvent(array $changes = ['status' => ['active', 'on_leave']], string $action = 'employee.updated'): AuditEventData
{
    return new AuditEventData(
        occurredAt: CarbonImmutable::parse('2026-11-10T08:15:02Z'),
        actorType: ActorType::User,
        actorId: '0193a4c9-0000-7000-8000-000000000001',
        action: $action,
        subjectType: 'employee',
        subjectId: '0193a4c2-0000-7000-8000-000000000002',
        changes: $changes,
        ipAddress: '10.0.0.1',
        userAgent: 'Pest',
        requestId: '0193a4c2-0000-7000-8000-000000000003',
    );
}

it('produces a deterministic SHA-256 hash', function (): void {
    expect(AuditChainHasher::hash(null, auditEvent()))
        ->toBe(AuditChainHasher::hash(null, auditEvent()))
        ->toHaveLength(64);
});

it('does not depend on the key order of the changes', function (): void {
    $a = auditEvent(['a' => [1, 2], 'b' => [3, 4]]);
    $b = auditEvent(['b' => [3, 4], 'a' => [1, 2]]);

    expect(AuditChainHasher::hash(null, $a))->toBe(AuditChainHasher::hash(null, $b));
});

it('changes when any field or the previous hash changes', function (): void {
    $base = AuditChainHasher::hash(null, auditEvent());

    expect(AuditChainHasher::hash(null, auditEvent(action: 'employee.deleted')))->not->toBe($base)
        ->and(AuditChainHasher::hash(null, auditEvent(['status' => ['active', 'notice']])))->not->toBe($base)
        ->and(AuditChainHasher::hash(str_repeat('a', 64), auditEvent()))->not->toBe($base);
});
