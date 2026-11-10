<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

/**
 * Personal data is only included for users holding employee.view.sensitive.
 * meta.permissions tells the UI which actions this user may take on this
 * record, so the UI never re-implements authorisation rules (docs/05 §3).
 *
 * @mixin Employee
 */
final class EmployeeResource extends JsonResource
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * @var array<string, Permission>
     */
    private const RECORD_ACTIONS = [
        'update' => Permission::EmployeeUpdate,
        'delete' => Permission::EmployeeDelete,
        'view_sensitive' => Permission::EmployeeViewSensitive,
        'update_sensitive' => Permission::EmployeeUpdateSensitive,
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'employee',
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'preferred_name' => $this->preferred_name,
            'display_name' => $this->displayName(),
            'work_email' => $this->work_email,
            'job_title' => $this->job_title,
            'employment_type' => $this->employment_type->value,
            'status' => $this->status->value,
            'hire_date' => $this->hire_date->format(self::DATE_FORMAT),
            'termination_date' => $this->termination_date?->format(self::DATE_FORMAT),
            'manager' => $this->whenLoaded('manager', fn (): ?array => $this->manager === null ? null : [
                'id' => $this->manager->id,
                'display_name' => $this->manager->displayName(),
            ]),
            'photo_url' => $this->hasPhoto() && ! $this->trashed() ? route('employees.photo.show', $this->id) : null,
            $this->mergeWhen($this->canSee($request), fn (): array => [
                'personal_email' => $this->personal_email,
                'phone' => $this->phone,
                'date_of_birth' => $this->date_of_birth,
            ]),
            'created_at' => $this->created_at->toIso8601ZuluString(),
            'updated_at' => $this->updated_at->toIso8601ZuluString(),
            'deleted_at' => $this->deleted_at?->toIso8601ZuluString(),
            'meta' => [
                'version' => $this->version,
                'permissions' => $this->allowedActions($request),
            ],
        ];
    }

    private function canSee(Request $request): bool
    {
        return (bool) $request->user()?->can(Permission::EmployeeViewSensitive->value);
    }

    /**
     * @return list<string>
     */
    private function allowedActions(Request $request): array
    {
        $user = $request->user();

        return array_keys(array_filter(
            self::RECORD_ACTIONS,
            static fn (Permission $permission): bool => (bool) $user?->can($permission->value),
        ));
    }
}
