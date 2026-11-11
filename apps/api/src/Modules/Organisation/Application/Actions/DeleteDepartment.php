<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Audit\Contracts\ChangeSet;
use Meridian\Modules\Organisation\Contracts\DepartmentReferenceChecker;
use Meridian\Modules\Organisation\Domain\Exceptions\DepartmentInUse;
use Meridian\Modules\Organisation\Domain\Exceptions\DepartmentNotFound;
use Meridian\Modules\Organisation\Domain\Repositories\DepartmentRepository;
use Meridian\Modules\Organisation\Domain\ValueObjects\DepartmentAuditAction;
use Meridian\Shared\Domain\Exceptions\StaleVersion;

/**
 * Deletes a department that has no sub-departments and is not referenced by
 * any other module (checked through the registered reference checkers).
 */
final readonly class DeleteDepartment
{
    /**
     * @param  iterable<DepartmentReferenceChecker>  $referenceCheckers
     */
    public function __construct(
        private DepartmentRepository $departments,
        private iterable $referenceCheckers,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    public function handle(string $departmentId, int $expectedVersion): void
    {
        $this->database->transaction(function () use ($departmentId, $expectedVersion): void {
            $department = $this->departments->findForUpdate($departmentId) ?? throw DepartmentNotFound::withId($departmentId);

            if ($department->version !== $expectedVersion) {
                throw StaleVersion::expected($expectedVersion, $department->version);
            }

            if ($this->departments->hasChildren($department->id)) {
                throw DepartmentInUse::hasSubDepartments();
            }

            foreach ($this->referenceCheckers as $checker) {
                if ($checker->isReferenced($department->id)) {
                    throw DepartmentInUse::hasMembers();
                }
            }

            $snapshot = $department->auditSnapshot();
            $this->departments->delete($department);

            $this->audit->record(new AuditRecord(
                action: DepartmentAuditAction::Deleted->value,
                subjectType: DepartmentAuditAction::SUBJECT_TYPE,
                subjectId: $department->id,
                changes: ChangeSet::between($snapshot, []),
            ));
        });
    }
}
