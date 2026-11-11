<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Contracts;

/**
 * Builds the field-level diff stored with an audit event. Sensitive fields
 * are recorded as changed, but their values are masked.
 */
final class ChangeSet
{
    public const MASK = '***';

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $sensitiveFields
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function between(array $before, array $after, array $sensitiveFields = []): array
    {
        $changes = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ($old === $new) {
                continue;
            }

            $changes[$field] = in_array($field, $sensitiveFields, true)
                ? [self::mask($old), self::mask($new)]
                : [$old, $new];
        }

        ksort($changes);

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $sensitiveFields
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function created(array $attributes, array $sensitiveFields = []): array
    {
        return self::between([], $attributes, $sensitiveFields);
    }

    private static function mask(mixed $value): ?string
    {
        return $value === null ? null : self::MASK;
    }
}
