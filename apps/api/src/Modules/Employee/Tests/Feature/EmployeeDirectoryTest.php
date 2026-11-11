<?php

declare(strict_types=1);

use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeStatus;
use Meridian\Modules\Employee\Domain\ValueObjects\EmploymentType;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

beforeEach(function (): void {
    $this->token = tokenForRole('hr_admin');
});

function directory(array $query = []): Illuminate\Testing\TestResponse
{
    return test()->withToken(test()->token)->getJson(route('employees.index', $query));
}

function names(Illuminate\Testing\TestResponse $response): array
{
    return array_column($response->json('data'), 'last_name');
}

it('paginates with opaque cursors', function (): void {
    Employee::factory()->count(5)->sequence(
        ['last_name' => 'Alwis'], ['last_name' => 'Bandara'], ['last_name' => 'Cooray'],
        ['last_name' => 'Dias'], ['last_name' => 'Ekanayake'],
    )->create();

    $first = directory(['per_page' => 2])->assertOk()->assertJsonPath('meta.per_page', 2)->assertJsonPath('meta.prev_cursor', null);
    $second = directory(['per_page' => 2, 'cursor' => $first->json('meta.next_cursor')]);
    $third = directory(['per_page' => 2, 'cursor' => $second->json('meta.next_cursor')]);

    expect(names($first))->toBe(['Alwis', 'Bandara'])
        ->and(names($second))->toBe(['Cooray', 'Dias'])
        ->and(names($third))->toBe(['Ekanayake'])
        ->and($third->json('meta.next_cursor'))->toBeNull()
        ->and($first->json('links.next'))->toContain('cursor=');
});

it('searches names, numbers and e-mail case-insensitively', function (): void {
    Employee::factory()->create(['first_name' => 'Nimal', 'last_name' => 'Perera']);
    Employee::factory()->create(['first_name' => 'Ayesha', 'last_name' => 'Fernando', 'work_email' => 'ayesha@meridian.test']);

    expect(names(directory(['q' => 'pere'])))->toBe(['Perera'])
        ->and(names(directory(['q' => 'AYESHA@'])))->toBe(['Fernando'])
        ->and(names(directory(['q' => '100%'])))->toBe([]);
});

it('filters by status (OR within a filter) and employment type (AND across filters)', function (): void {
    Employee::factory()->withStatus(EmployeeStatus::Active)->create(['last_name' => 'Active']);
    Employee::factory()->withStatus(EmployeeStatus::OnLeave)->create(['last_name' => 'OnLeave']);
    Employee::factory()->withStatus(EmployeeStatus::Notice)->create(['last_name' => 'Notice']);
    Employee::factory()->withStatus(EmployeeStatus::Active)->create(['last_name' => 'Contractor', 'employment_type' => EmploymentType::Contract]);

    expect(names(directory(['filter' => ['status' => 'active,on_leave']])))->toBe(['Active', 'Contractor', 'OnLeave'])
        ->and(names(directory(['filter' => ['status' => 'active', 'employment_type' => 'contract']])))->toBe(['Contractor']);
});

it('filters by manager and hire date range', function (): void {
    $manager = Employee::factory()->create(['last_name' => 'Manager', 'hire_date' => '2015-01-01']);
    Employee::factory()->reportingTo($manager)->create(['last_name' => 'Early', 'hire_date' => '2020-01-15']);
    Employee::factory()->reportingTo($manager)->create(['last_name' => 'Late', 'hire_date' => '2024-06-01']);

    expect(names(directory(['filter' => ['manager_id' => $manager->id]])))->toBe(['Early', 'Late'])
        ->and(names(directory(['filter' => ['hire_date' => ['gte' => '2020-01-01', 'lte' => '2021-12-31']]])))->toBe(['Early']);
});

it('sorts by whitelisted fields in either direction', function (): void {
    Employee::factory()->create(['last_name' => 'Old', 'hire_date' => '2010-01-01']);
    Employee::factory()->create(['last_name' => 'New', 'hire_date' => '2025-01-01']);

    expect(names(directory(['sort' => '-hire_date'])))->toBe(['New', 'Old'])
        ->and(names(directory(['sort' => 'hire_date'])))->toBe(['Old', 'New']);
});

it('rejects unknown filters, sort fields and values', function (array $query, string $field): void {
    directory($query)->assertUnprocessable()->assertJsonStructure(['errors' => [$field]]);
})->with([
    'unknown filter' => [['filter' => ['salary' => '1']], 'filter'],
    'unknown status' => [['filter' => ['status' => 'active,retired']], 'filter.status'],
    'unknown sort field' => [['sort' => 'salary'], 'sort'],
    'duplicate sort field' => [['sort' => 'last_name,-last_name'], 'sort'],
    'page too large' => [['per_page' => 500], 'per_page'],
]);

it('excludes soft-deleted employees unless asked', function (): void {
    Employee::factory()->create(['last_name' => 'Current']);
    Employee::factory()->create(['last_name' => 'Former'])->delete();

    expect(names(directory()))->toBe(['Current'])
        ->and(names(directory(['filter' => ['trashed' => 'only']])))->toBe(['Former'])
        ->and(names(directory(['filter' => ['trashed' => 'with']])))->toBe(['Current', 'Former']);
});
