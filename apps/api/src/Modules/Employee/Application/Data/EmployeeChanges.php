<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Application\Data;

use InvalidArgumentException;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

/**
 * A partial update (PATCH): only the fields the client sent.
 */
final readonly class EmployeeChanges
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(public array $attributes)
    {
        $unknown = array_diff(array_keys($attributes), Employee::EDITABLE_FIELDS);

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown employee fields: '.implode(', ', $unknown));
        }
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->attributes);
    }

    public function get(string $field): mixed
    {
        return $this->attributes[$field] ?? null;
    }
}
