<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Meridian\Modules\Employee\Application\Actions\CreateEmployee;
use Meridian\Modules\Employee\Application\Actions\DeleteEmployee;
use Meridian\Modules\Employee\Application\Actions\RestoreEmployee;
use Meridian\Modules\Employee\Application\Actions\UpdateEmployee;
use Meridian\Modules\Employee\Application\Queries\GetEmployee;
use Meridian\Modules\Employee\Application\Queries\ListEmployees;
use Meridian\Modules\Employee\Http\Requests\DeleteEmployeeRequest;
use Meridian\Modules\Employee\Http\Requests\ListEmployeesRequest;
use Meridian\Modules\Employee\Http\Requests\StoreEmployeeRequest;
use Meridian\Modules\Employee\Http\Requests\UpdateEmployeeRequest;
use Meridian\Modules\Employee\Http\Resources\EmployeeResource;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;
use Meridian\Shared\Http\Concurrency\EntityTag;
use Meridian\Shared\Http\Resources\CursorPageResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

final readonly class EmployeeController
{
    private const LOCATION_HEADER = 'Location';

    /**
     * GET /employees — search, filter, sort, cursor-paginate.
     */
    public function index(ListEmployeesRequest $request, ListEmployees $listEmployees): AnonymousResourceCollection
    {
        return CursorPageResponse::make($listEmployees->handle($request->toCriteria()), EmployeeResource::class, $request);
    }

    /**
     * POST /employees
     */
    public function store(StoreEmployeeRequest $request, CreateEmployee $createEmployee): JsonResponse
    {
        $employee = $createEmployee->handle($request->toData(), $request->actorId());

        return $this->respond($employee, HttpStatus::HTTP_CREATED)
            ->header(self::LOCATION_HEADER, route('employees.show', $employee->id));
    }

    /**
     * GET /employees/{employee}
     */
    public function show(string $employee, GetEmployee $getEmployee): JsonResponse
    {
        return $this->respond($getEmployee->handle($employee));
    }

    /**
     * PATCH /employees/{employee} — requires If-Match.
     */
    public function update(UpdateEmployeeRequest $request, string $employee, UpdateEmployee $updateEmployee): JsonResponse
    {
        return $this->respond($updateEmployee->handle($employee, $request->toChanges(), $request->expectedVersion()));
    }

    /**
     * DELETE /employees/{employee} — soft delete with a reason, requires If-Match.
     */
    public function destroy(DeleteEmployeeRequest $request, string $employee, DeleteEmployee $deleteEmployee): Response
    {
        $deleteEmployee->handle($employee, $request->expectedVersion(), $request->reason());

        return response()->noContent();
    }

    /**
     * POST /employees/{employee}/restore
     */
    public function restore(string $employee, RestoreEmployee $restoreEmployee): JsonResponse
    {
        return $this->respond($restoreEmployee->handle($employee));
    }

    private function respond(Employee $employee, int $status = HttpStatus::HTTP_OK): JsonResponse
    {
        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode($status)
            ->header(EntityTag::RESPONSE_HEADER, EntityTag::forVersion($employee->version));
    }
}
