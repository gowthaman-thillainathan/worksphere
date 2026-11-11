<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Meridian\Shared\Http\Concurrency\EntityTag;

/**
 * DELETE /employees/{employee} with { "reason": "…" } and If-Match.
 */
final class DeleteEmployeeRequest extends FormRequest
{
    public const REASON_MIN_LENGTH = 5;

    public const REASON_MAX_LENGTH = 500;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:'.self::REASON_MIN_LENGTH, 'max:'.self::REASON_MAX_LENGTH],
        ];
    }

    public function reason(): string
    {
        return $this->string('reason')->trim()->toString();
    }

    public function expectedVersion(): int
    {
        return EntityTag::requiredVersion($this);
    }
}
