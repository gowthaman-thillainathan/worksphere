<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use BackedEnum;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeSearchCriteria;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeSort;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeSortField;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;
use Meridian\Modules\Employee\Domain\ValueObjects\TrashedFilter;
use Meridian\Shared\Http\Query\QueryString;
use Meridian\Shared\Http\Query\SortInstruction;

/**
 * GET /employees?q=&filter[...]=&sort=&per_page=&cursor= (docs/05 §4).
 *
 * Every filter and sort field is whitelisted; anything else is a 422.
 */
final class ListEmployeesRequest extends FormRequest
{
    private const DATE_FORMAT = 'date_format:Y-m-d';

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:100'],
            'filter' => ['sometimes', 'array:status,employment_type,manager_id,department_id,hire_date,trashed'],
            'filter.status' => ['sometimes', 'string', self::csvOf(EmployeeStatus::class)],
            'filter.employment_type' => ['sometimes', 'string', self::csvOf(EmploymentType::class)],
            'filter.manager_id' => ['sometimes', 'uuid'],
            'filter.department_id' => ['sometimes', 'uuid'],
            'filter.hire_date' => ['sometimes', 'array:gte,lte'],
            'filter.hire_date.gte' => ['sometimes', self::DATE_FORMAT],
            'filter.hire_date.lte' => ['sometimes', self::DATE_FORMAT],
            'filter.trashed' => ['sometimes', Rule::in([TrashedFilter::With->value, TrashedFilter::Only->value])],
            'sort' => ['sometimes', 'string', self::validSort()],
            'per_page' => ['sometimes', 'integer', 'between:1,'.config('employee.pagination.max_per_page')],
            'cursor' => ['sometimes', 'string', 'max:2000'],
        ];
    }

    public function toCriteria(): EmployeeSearchCriteria
    {
        return new EmployeeSearchCriteria(
            search: $this->input('q'),
            statuses: array_map(EmployeeStatus::from(...), QueryString::list((string) $this->input('filter.status', ''))),
            employmentTypes: array_map(EmploymentType::from(...), QueryString::list((string) $this->input('filter.employment_type', ''))),
            managerId: $this->input('filter.manager_id'),
            departmentId: $this->input('filter.department_id'),
            hiredFrom: $this->date('filter.hire_date.gte')?->toImmutable(),
            hiredTo: $this->date('filter.hire_date.lte')?->toImmutable(),
            trashed: TrashedFilter::tryFrom((string) $this->input('filter.trashed')) ?? TrashedFilter::Without,
            sort: array_map(
                static fn (SortInstruction $sort): EmployeeSort => new EmployeeSort(EmployeeSortField::from($sort->field), $sort->direction),
                QueryString::sort((string) $this->input('sort', '')),
            ),
            perPage: $this->integer('per_page', EmployeeSearchCriteria::DEFAULT_PER_PAGE),
            cursor: $this->input('cursor'),
        );
    }

    /**
     * Comma-separated values that must all be cases of the enum.
     *
     * @param  class-string<BackedEnum>  $enum
     */
    private static function csvOf(string $enum): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($enum): void {
            $values = QueryString::list((string) $value);
            $invalid = array_filter($values, static fn (string $item): bool => $enum::tryFrom($item) === null);

            if ($values === [] || $invalid !== []) {
                $fail(sprintf('The :attribute must be a comma-separated list of: %s.', implode(', ', array_column($enum::cases(), 'value'))));
            }
        };
    }

    private static function validSort(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            $fields = QueryString::sortFields((string) $value);
            $allowed = array_column(EmployeeSortField::cases(), 'value');

            if ($fields === [] || array_diff($fields, $allowed) !== [] || count($fields) !== count(array_unique($fields))) {
                $fail(sprintf('The :attribute must list distinct fields from: %s (prefix with - for descending).', implode(', ', $allowed)));
            }
        };
    }
}
