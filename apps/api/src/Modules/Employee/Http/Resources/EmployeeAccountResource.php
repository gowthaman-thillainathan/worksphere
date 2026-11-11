<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeAccount;

/**
 * @property EmployeeAccount $resource
 */
final class EmployeeAccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->resource->userId,
            'type' => 'employee_account',
            'email' => $this->resource->email,
            'status' => $this->resource->status,
            'email_verified' => $this->resource->emailVerified,
            'role' => $this->resource->role,
            'last_login_at' => $this->resource->lastLoginAt?->format(DateTimeInterface::RFC3339),
            'created_at' => $this->resource->createdAt->format(DateTimeInterface::RFC3339),
        ];
    }
}
