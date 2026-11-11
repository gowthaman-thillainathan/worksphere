<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Meridian\Shared\Http\CurrentActor;

/**
 * { "role": "hr_admin" } for creating an account or changing its role.
 */
final class EmployeeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AuthorizesAccountManagement::allows($this);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:50'],
        ];
    }

    public function role(): string
    {
        return $this->string('role')->toString();
    }

    public function actorId(): ?string
    {
        return CurrentActor::id($this);
    }
}
