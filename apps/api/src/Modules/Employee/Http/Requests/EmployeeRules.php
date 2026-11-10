<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use Illuminate\Validation\Rule;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;

/**
 * Field rules shared by create (all required fields present) and update
 * (any subset, but never blank). Cross-record rules — unique work e-mail,
 * valid manager, consistent dates — are enforced by the domain.
 */
final class EmployeeRules
{
    private const DATE_FORMAT = 'date_format:Y-m-d';

    private const PHONE_PATTERN = 'regex:/^\+?[0-9 ()\-]{7,20}$/';

    private const MINIMUM_AGE = 'before:-16 years';

    /**
     * @return array<string, list<mixed>>
     */
    public static function forCreate(): array
    {
        return self::rules(['required']);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function forUpdate(): array
    {
        return self::rules(['sometimes', 'required']);
    }

    /**
     * @param  list<string>  $presence
     * @return array<string, list<mixed>>
     */
    private static function rules(array $presence): array
    {
        return [
            'first_name' => [...$presence, 'string', 'max:100'],
            'last_name' => [...$presence, 'string', 'max:100'],
            'preferred_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'work_email' => [...$presence, 'string', 'email:rfc', 'max:255'],
            'job_title' => [...$presence, 'string', 'max:150'],
            'employment_type' => [...$presence, Rule::enum(EmploymentType::class)],
            'status' => ['sometimes', 'required', Rule::enum(EmployeeStatus::class)],
            'hire_date' => [...$presence, self::DATE_FORMAT],
            'termination_date' => ['sometimes', 'nullable', self::DATE_FORMAT],
            'manager_id' => ['sometimes', 'nullable', 'uuid'],
            'personal_email' => ['sometimes', 'nullable', 'string', 'email:rfc', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', self::PHONE_PATTERN],
            'date_of_birth' => ['sometimes', 'nullable', self::DATE_FORMAT, self::MINIMUM_AGE],
            // Server-managed fields can never be written by clients.
            'id' => ['prohibited'],
            'employee_number' => ['prohibited'],
            'version' => ['prohibited'],
            'photo_path' => ['prohibited'],
        ];
    }
}
