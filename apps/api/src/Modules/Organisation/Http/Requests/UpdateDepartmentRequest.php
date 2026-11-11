<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Meridian\Modules\Organisation\Application\Data\DepartmentChanges;
use Meridian\Shared\Http\Concurrency\EntityTag;

final class UpdateDepartmentRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return DepartmentRules::forUpdate();
    }

    public function toChanges(): DepartmentChanges
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return new DepartmentChanges($validated);
    }

    public function expectedVersion(): int
    {
        return EntityTag::requiredVersion($this);
    }
}
