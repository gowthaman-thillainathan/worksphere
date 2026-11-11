<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Audit\Contracts\ChangeSet;
use Meridian\Modules\Organisation\Application\Data\DepartmentChanges;
use Meridian\Modules\Organisation\Domain\Exceptions\DepartmentNotFound;
use Meridian\Modules\Organisation\Domain\Repositories\DepartmentRepository;
use Meridian\Modules\Organisation\Domain\Services\DepartmentInvariants;
use Meridian\Modules\Organisation\Domain\ValueObjects\DepartmentAuditAction;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;
use Meridian\Shared\Domain\Exceptions\StaleVersion;

/**
 * Partial update with optimistic locking (If-Match).
 */
final readonly class UpdateDepartment
{
    public function __construct(
        private DepartmentRepository $departments,
        private DepartmentInvariants $invariants,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    public function handle(string $departmentId, DepartmentChanges $changes, int $expectedVersion): Department
    {
        $this->database->transaction(function () use ($departmentId, $changes, $expectedVersion): void {
            $department = $this->departments->findForUpdate($departmentId) ?? throw DepartmentNotFound::withId($departmentId);

            if ($department->version !== $expectedVersion) {
                throw StaleVersion::expected($expectedVersion, $department->version);
            }

            if ($changes->has('code')) {
                $this->invariants->assertCodeAvailable((string) $changes->get('code'), $department->id);
            }

            if ($changes->has('parent_id')) {
                $this->invariants->assertValidParent($department->id, $changes->get('parent_id'));
            }

            $before = $department->auditSnapshot();
            $department->fill($changes->attributes);
            $diff = ChangeSet::between($before, $department->auditSnapshot());

            if ($diff === []) {
                return;
            }

            $department->version++;
            $this->departments->save($department);

            $this->audit->record(new AuditRecord(
                action: DepartmentAuditAction::Updated->value,
                subjectType: DepartmentAuditAction::SUBJECT_TYPE,
                subjectId: $department->id,
                changes: $diff,
            ));
        });

        return $this->departments->find($departmentId) ?? throw DepartmentNotFound::withId($departmentId);
    }
}
