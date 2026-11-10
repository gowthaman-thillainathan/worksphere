<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Meridian\Shared\Http\Responses\ApiExceptionRenderer;

it('never leaks internal error details', function (): void {
    $response = app(ApiExceptionRenderer::class)->render(
        new RuntimeException('SQLSTATE[42P01]: relation "secrets" does not exist'),
        Request::create('/api/v1/anything'),
    );

    $body = json_decode((string) $response->getContent(), true);

    expect($response->getStatusCode())->toBe(500)
        ->and($body['code'])->toBe('internal_error')
        ->and((string) $response->getContent())->not->toContain('SQLSTATE');
});
