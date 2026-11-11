<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Actions;

use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Audit\Contracts\AuditRecord;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Employee\Application\Data\UploadedPhoto;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeePhotoStorage;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAuditAction;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use RuntimeException;
use Throwable;

/**
 * Replaces the profile photo. The new file is removed again if the database
 * transaction fails; the old file is removed only after a successful commit.
 */
final readonly class UpdateEmployeePhoto
{
    private const READ_BINARY = 'rb';

    public function __construct(
        private EmployeeRepository $employees,
        private EmployeePhotoStorage $photos,
        private AuditRecorder $audit,
        private ConnectionInterface $database,
    ) {}

    public function handle(string $employeeId, UploadedPhoto $photo): Employee
    {
        $newPath = null;

        try {
            $previousPath = $this->database->transaction(function () use ($employeeId, $photo, &$newPath): ?string {
                $employee = $this->employees->findForUpdate($employeeId) ?? throw EmployeeNotFound::withId($employeeId);
                $previousPath = $employee->photo_path;

                $newPath = $this->storeFile($employee, $photo);
                $employee->forceFill([
                    'photo_path' => $newPath,
                    'photo_mime_type' => $photo->mimeType,
                    'version' => $employee->version + 1,
                ]);
                $this->employees->save($employee);

                $this->audit->record(new AuditRecord(
                    action: EmployeeAuditAction::PhotoUpdated->value,
                    subjectType: EmployeeAuditAction::SUBJECT_TYPE,
                    subjectId: $employee->id,
                    changes: ['photo' => [$previousPath !== null, true]],
                ));

                return $previousPath;
            });
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                $this->photos->delete($newPath);
            }

            throw $exception;
        }

        if ($previousPath !== null) {
            $this->photos->delete($previousPath);
        }

        return $this->employees->find($employeeId) ?? throw EmployeeNotFound::withId($employeeId);
    }

    private function storeFile(Employee $employee, UploadedPhoto $photo): string
    {
        $stream = fopen($photo->temporaryPath, self::READ_BINARY)
            ?: throw new RuntimeException('Uploaded photo is not readable.');

        try {
            return $this->photos->store($employee->id, $stream, $photo->extension);
        } finally {
            fclose($stream);
        }
    }
}
