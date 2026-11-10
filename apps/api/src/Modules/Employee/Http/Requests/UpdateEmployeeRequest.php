<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Meridian\Modules\Employee\Application\Data\EmployeeChanges;
use Meridian\Shared\Http\Concurrency\EntityTag;

final class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AuthorizesSensitiveFields::allows($this);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return EmployeeRules::forUpdate();
    }

    public function toChanges(): EmployeeChanges
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return new EmployeeChanges($validated);
    }

    public function expectedVersion(): int
    {
        return EntityTag::requiredVersion($this);
    }
}
