<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeePhotoNotFound;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeePhotoStorage;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAuditAction;

final readonly class RemoveEmployeePhoto
{
    public function __construct(
        private EmployeeRepository $employees,
        private EmployeePhotoStorage $photos,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    public function handle(string $employeeId): void
    {
        $removedPath = $this->database->transaction(function () use ($employeeId): string {
            $employee = $this->employees->findForUpdate($employeeId) ?? throw EmployeeNotFound::withId($employeeId);
            $path = $employee->photo_path ?? throw new EmployeePhotoNotFound;

            $employee->forceFill(['photo_path' => null, 'photo_mime_type' => null, 'version' => $employee->version + 1]);
            $this->employees->save($employee);

            $this->audit->record(new AuditRecord(
                action: EmployeeAuditAction::PhotoRemoved->value,
                subjectType: EmployeeAuditAction::SUBJECT_TYPE,
                subjectId: $employee->id,
                changes: ['photo' => [true, false]],
            ));

            return $path;
        });

        $this->photos->delete($removedPath);
    }
}
