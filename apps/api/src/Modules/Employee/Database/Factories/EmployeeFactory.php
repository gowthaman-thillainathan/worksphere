<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

/**
 * @extends Factory<Employee>
 */
final class EmployeeFactory extends Factory
{
    /**
     * @var class-string<Employee>
     */
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_number' => 'TEST-'.fake()->unique()->numerify('######'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'work_email' => fake()->unique()->safeEmail(),
            'personal_email' => fake()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'date_of_birth' => CarbonImmutable::instance(fake()->dateTimeBetween('-60 years', '-20 years'))->format('Y-m-d'),
            'job_title' => fake()->jobTitle(),
            'employment_type' => EmploymentType::FullTime,
            'status' => EmployeeStatus::Active,
            'hire_date' => CarbonImmutable::instance(fake()->dateTimeBetween('-10 years', '-1 month')),
        ];
    }

    public function reportingTo(Employee $manager): self
    {
        return $this->state(['manager_id' => $manager->id]);
    }

    public function withStatus(EmployeeStatus $status): self
    {
        return $this->state(['status' => $status]);
    }
}
