<?php

declare(strict_types=1);

use Meridian\Shared\Http\Responses\CommonErrorCode;

it('maps HTTP status codes to stable error codes', function (int $status, CommonErrorCode $expected): void {
    expect(CommonErrorCode::fromStatus($status))->toBe($expected);
})->with([
    [400, CommonErrorCode::BadRequest],
    [401, CommonErrorCode::Unauthenticated],
    [403, CommonErrorCode::Forbidden],
    [404, CommonErrorCode::NotFound],
    [405, CommonErrorCode::MethodNotAllowed],
    [429, CommonErrorCode::RateLimited],
    [418, CommonErrorCode::HttpError],
    [502, CommonErrorCode::InternalError],
]);
