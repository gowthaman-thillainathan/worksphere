<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Query;

use Meridian\Shared\Domain\ValueObjects\SortDirection;

/**
 * Parses the list-endpoint conventions of docs/05 §4:
 *   sort=last_name,-hire_date   (minus = descending)
 *   filter[status]=active,on_leave   (comma = OR)
 */
final class QueryString
{
    private const LIST_SEPARATOR = ',';

    private const DESCENDING_PREFIX = '-';

    /**
     * @return list<SortInstruction>
     */
    public static function sort(string $value): array
    {
        return array_map(
            static fn (string $token): SortInstruction => str_starts_with($token, self::DESCENDING_PREFIX)
                ? new SortInstruction(substr($token, 1), SortDirection::Descending)
                : new SortInstruction($token, SortDirection::Ascending),
            self::list($value),
        );
    }

    /**
     * @return list<string> Sort field names without direction prefixes.
     */
    public static function sortFields(string $value): array
    {
        return array_map(static fn (SortInstruction $sort): string => $sort->field, self::sort($value));
    }

    /**
     * @return list<string>
     */
    public static function list(string $value): array
    {
        return array_values(array_filter(
            array_map(trim(...), explode(self::LIST_SEPARATOR, $value)),
            static fn (string $item): bool => $item !== '',
        ));
    }
}
