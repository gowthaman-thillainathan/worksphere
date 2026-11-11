<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Audit\Contracts\ChangeSet;
use Meridian\Modules\Organisation\Application\Data\DepartmentData;
use Meridian\Modules\Organisation\Domain\Exceptions\DepartmentNotFound;
use Meridian\Modules\Organisation\Domain\Repositories\DepartmentRepository;
use Meridian\Modules\Organisation\Domain\Services\DepartmentInvariants;
use Meridian\Modules\Organisation\Domain\ValueObjects\DepartmentAuditAction;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

final readonly class CreateDepartment
{
    public function __construct(
        private DepartmentRepository $departments,
        private DepartmentInvariants $invariants,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    public function handle(DepartmentData $data): Department
    {
        $departmentId = $this->database->transaction(function () use ($data): string {
            $this->invariants->assertCodeAvailable($data->code);
            $this->invariants->assertValidParent(null, $data->parentId);

            $department = new Department($data->toAttributes());
            $this->departments->save($department);

            $this->audit->record(new AuditRecord(
                action: DepartmentAuditAction::Created->value,
                subjectType: DepartmentAuditAction::SUBJECT_TYPE,
                subjectId: $department->id,
                changes: ChangeSet::created($department->auditSnapshot()),
            ));

            return $department->id;
        });

        return $this->departments->find($departmentId) ?? throw DepartmentNotFound::withId($departmentId);
    }
}
