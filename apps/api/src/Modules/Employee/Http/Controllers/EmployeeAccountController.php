<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Meridian\Modules\Employee\Application\Actions\ChangeEmployeeRole;
use Meridian\Modules\Employee\Application\Actions\CreateEmployeeAccount;
use Meridian\Modules\Employee\Application\Queries\GetEmployeeAccount;
use Meridian\Modules\Employee\Http\Requests\EmployeeRoleRequest;
use Meridian\Modules\Employee\Http\Resources\EmployeeAccountResource;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

/**
 * The employee's sign-in account (ADR-0007).
 */
final readonly class EmployeeAccountController
{
    /**
     * GET /employees/{employee}/account
     */
    public function show(string $employee, GetEmployeeAccount $getAccount): EmployeeAccountResource
    {
        return new EmployeeAccountResource($getAccount->handle($employee));
    }

    /**
     * POST /employees/{employee}/account — invites the employee.
     */
    public function store(EmployeeRoleRequest $request, string $employee, CreateEmployeeAccount $createAccount): JsonResponse
    {
        return (new EmployeeAccountResource($createAccount->handle($employee, $request->role(), $request->actorId())))
            ->response()
            ->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * PUT /employees/{employee}/account/role
     */
    public function updateRole(EmployeeRoleRequest $request, string $employee, ChangeEmployeeRole $changeRole): EmployeeAccountResource
    {
        return new EmployeeAccountResource($changeRole->handle($employee, $request->role(), $request->actorId()));
    }
}
