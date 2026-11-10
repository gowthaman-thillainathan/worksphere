<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Queries;

use Meridian\Modules\Employee\Application\Data\EmployeePhoto;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeeNotFound;
use Meridian\Modules\Employee\Domain\Exceptions\EmployeePhotoNotFound;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\EmployeePhotoStorage;

final readonly class GetEmployeePhoto
{
    public function __construct(
        private EmployeeRepository $employees,
        private EmployeePhotoStorage $photos,
    ) {}

    public function handle(string $employeeId): EmployeePhoto
    {
        $employee = $this->employees->find($employeeId) ?? throw EmployeeNotFound::withId($employeeId);

        if ($employee->photo_path === null || $employee->photo_mime_type === null) {
            throw new EmployeePhotoNotFound;
        }

        $stream = $this->photos->read($employee->photo_path) ?? throw new EmployeePhotoNotFound;

        return new EmployeePhoto($stream, $employee->photo_mime_type);
    }
}
