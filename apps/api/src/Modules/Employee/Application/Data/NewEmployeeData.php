<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Data;

use DateTimeImmutable;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;

final readonly class NewEmployeeData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $workEmail,
        public string $jobTitle,
        public EmploymentType $employmentType,
        public DateTimeImmutable $hireDate,
        public EmployeeStatus $status = EmployeeStatus::Active,
        public ?string $preferredName = null,
        public ?DateTimeImmutable $terminationDate = null,
        public ?string $managerId = null,
        public ?string $personalEmail = null,
        public ?string $phone = null,
        public ?string $dateOfBirth = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'preferred_name' => $this->preferredName,
            'work_email' => $this->workEmail,
            'job_title' => $this->jobTitle,
            'employment_type' => $this->employmentType,
            'status' => $this->status,
            'hire_date' => $this->hireDate,
            'termination_date' => $this->terminationDate,
            'manager_id' => $this->managerId,
            'personal_email' => $this->personalEmail,
            'phone' => $this->phone,
            'date_of_birth' => $this->dateOfBirth,
        ];
    }
}
