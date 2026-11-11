<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Meridian\Modules\Employee\Application\Queries\GetHeadcount;

final readonly class HeadcountController
{
    /**
     * GET /employees/headcount — active employees in total and per status.
     */
    public function __invoke(GetHeadcount $getHeadcount): JsonResponse
    {
        $headcount = $getHeadcount->handle();

        return new JsonResponse(['data' => [
            'total' => $headcount->total(),
            'by_status' => $headcount->byStatus,
        ]]);
    }
}
