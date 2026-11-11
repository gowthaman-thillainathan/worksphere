<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Meridian\Modules\Organisation\Application\Data\DepartmentData;

final class StoreDepartmentRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return DepartmentRules::forCreate();
    }

    public function toData(): DepartmentData
    {
        return new DepartmentData(
            code: $this->string('code')->toString(),
            name: $this->string('name')->toString(),
            description: $this->input('description'),
            parentId: $this->input('parent_id'),
        );
    }
}
