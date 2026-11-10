<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Meridian\Shared\Http\Concurrency\EntityTag;
use Symfony\Component\HttpKernel\Exception\HttpException;

function requestWithIfMatch(?string $value): Request
{
    $request = Request::create('/api/v1/employees/x', 'PATCH');

    if ($value !== null) {
        $request->headers->set('If-Match', $value);
    }

    return $request;
}

it('formats versions as quoted entity tags', function (): void {
    expect(EntityTag::forVersion(7))->toBe('"v7"');
});

it('reads strong and weak If-Match headers', function (string $header, int $version): void {
    expect(EntityTag::requiredVersion(requestWithIfMatch($header)))->toBe($version);
})->with([
    'strong' => ['"v7"', 7],
    'weak' => ['W/"v12"', 12],
]);

it('requires the If-Match header (428)', function (): void {
    expect(fn () => EntityTag::requiredVersion(requestWithIfMatch(null)))
        ->toThrow(fn (HttpException $e) => expect($e->getStatusCode())->toBe(428));
});

it('rejects malformed If-Match headers (412)', function (string $header): void {
    expect(fn () => EntityTag::requiredVersion(requestWithIfMatch($header)))
        ->toThrow(fn (HttpException $e) => expect($e->getStatusCode())->toBe(412));
})->with(['*', 'v7', '"7"', '"vx"']);
