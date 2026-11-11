<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Application\Data;

use InvalidArgumentException;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

/**
 * A partial update (PATCH): only the fields the client sent.
 */
final readonly class DepartmentChanges
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(public array $attributes)
    {
        $unknown = array_diff(array_keys($attributes), Department::EDITABLE_FIELDS);

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown department fields: '.implode(', ', $unknown));
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
