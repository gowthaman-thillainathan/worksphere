<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Http\Requests;

final class DepartmentRules
{
    private const CODE_PATTERN = 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/';

    /**
     * @return array<string, list<string>>
     */
    public static function forCreate(): array
    {
        return self::rules(['required']);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function forUpdate(): array
    {
        return self::rules(['sometimes', 'required']);
    }

    /**
     * @param  list<string>  $presence
     * @return array<string, list<string>>
     */
    private static function rules(array $presence): array
    {
        return [
            'code' => [...$presence, 'string', 'max:20', self::CODE_PATTERN],
            'name' => [...$presence, 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'parent_id' => ['sometimes', 'nullable', 'uuid'],
            'id' => ['prohibited'],
            'version' => ['prohibited'],
        ];
    }
}
