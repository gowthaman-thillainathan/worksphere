<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Meridian\Modules\Organisation\Application\Actions\CreateDepartment;
use Meridian\Modules\Organisation\Application\Actions\DeleteDepartment;
use Meridian\Modules\Organisation\Application\Actions\UpdateDepartment;
use Meridian\Modules\Organisation\Application\Queries\GetDepartment;
use Meridian\Modules\Organisation\Application\Queries\ListDepartments;
use Meridian\Modules\Organisation\Http\Requests\StoreDepartmentRequest;
use Meridian\Modules\Organisation\Http\Requests\UpdateDepartmentRequest;
use Meridian\Modules\Organisation\Http\Resources\DepartmentResource;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;
use Meridian\Shared\Http\Concurrency\EntityTag;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

final readonly class DepartmentController
{
    private const LOCATION_HEADER = 'Location';

    /**
     * GET /departments — every department, ordered by name.
     */
    public function index(ListDepartments $listDepartments): AnonymousResourceCollection
    {
        $departments = $listDepartments->handle();

        return DepartmentResource::collection($departments)->additional(['meta' => ['total' => count($departments)]]);
    }

    /**
     * POST /departments
     */
    public function store(StoreDepartmentRequest $request, CreateDepartment $createDepartment): JsonResponse
    {
        $department = $createDepartment->handle($request->toData());

        return $this->respond($department, HttpStatus::HTTP_CREATED)
            ->header(self::LOCATION_HEADER, route('departments.show', $department->id));
    }

    /**
     * GET /departments/{department}
     */
    public function show(string $department, GetDepartment $getDepartment): JsonResponse
    {
        return $this->respond($getDepartment->handle($department));
    }

    /**
     * PATCH /departments/{department} — requires If-Match.
     */
    public function update(UpdateDepartmentRequest $request, string $department, UpdateDepartment $updateDepartment): JsonResponse
    {
        return $this->respond($updateDepartment->handle($department, $request->toChanges(), $request->expectedVersion()));
    }

    /**
     * DELETE /departments/{department} — requires If-Match.
     */
    public function destroy(Request $request, string $department, DeleteDepartment $deleteDepartment): Response
    {
        $deleteDepartment->handle($department, EntityTag::requiredVersion($request));

        return response()->noContent();
    }

    private function respond(Department $department, int $status = HttpStatus::HTTP_OK): JsonResponse
    {
        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode($status)
            ->header(EntityTag::RESPONSE_HEADER, EntityTag::forVersion($department->version));
    }
}
