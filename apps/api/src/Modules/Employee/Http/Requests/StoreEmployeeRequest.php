<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Meridian\Modules\Employee\Application\Data\NewEmployeeData;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;
use Meridian\Shared\Http\CurrentActor;

/**
 * POST /employees. An optional "account": {"role": "…"} also creates a
 * sign-in account, which requires user.manage and access.manage.
 */
final class StoreEmployeeRequest extends FormRequest
{
    private const ACCOUNT = 'account';

    private const ACCOUNT_ROLE = 'account.role';

    public function authorize(): bool
    {
        return AuthorizesSensitiveFields::allows($this)
            && (! $this->has(self::ACCOUNT) || AuthorizesAccountManagement::allows($this));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...EmployeeRules::forCreate(),
            self::ACCOUNT => ['sometimes', 'array:role'],
            self::ACCOUNT_ROLE => ['required_with:'.self::ACCOUNT, 'string', 'max:50'],
        ];
    }

    public function toData(): NewEmployeeData
    {
        return new NewEmployeeData(
            firstName: $this->string('first_name')->toString(),
            lastName: $this->string('last_name')->toString(),
            workEmail: $this->string('work_email')->toString(),
            jobTitle: $this->string('job_title')->toString(),
            employmentType: $this->enum('employment_type', EmploymentType::class) ?? EmploymentType::FullTime,
            hireDate: CarbonImmutable::parse($this->string('hire_date')->toString()),
            status: $this->enum('status', EmployeeStatus::class) ?? EmployeeStatus::Active,
            preferredName: $this->input('preferred_name'),
            terminationDate: $this->filled('termination_date') ? CarbonImmutable::parse($this->string('termination_date')->toString()) : null,
            managerId: $this->input('manager_id'),
            departmentId: $this->input('department_id'),
            personalEmail: $this->input('personal_email'),
            phone: $this->input('phone'),
            dateOfBirth: $this->input('date_of_birth'),
            accountRole: $this->input(self::ACCOUNT_ROLE),
        );
    }

    public function actorId(): ?string
    {
        return CurrentActor::id($this);
    }
}
