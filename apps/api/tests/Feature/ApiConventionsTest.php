<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Meridian\Modules\Identity\Http\RouteName;

it('answers unknown API routes with a problem document', function (): void {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('type', 'https://docs.meridian.app/errors/not_found')
        ->assertJsonPath('status', 404)
        ->assertJsonPath('code', 'not_found')
        ->assertJsonPath('instance', '/api/v1/does-not-exist');
});

it('correlates every response with a request id', function (): void {
    $response = $this->getJson('/api/v1/does-not-exist');

    expect(Str::isUuid($response->headers->get('X-Request-Id')))->toBeTrue()
        ->and($response->json('request_id'))->toBe($response->headers->get('X-Request-Id'));
});

it('propagates a caller-supplied request id', function (): void {
    $requestId = (string) Str::uuid7();

    $this->withHeader('X-Request-Id', $requestId)
        ->getJson(route(RouteName::CurrentUser->value))
        ->assertHeader('X-Request-Id', $requestId)
        ->assertJsonPath('request_id', $requestId);
});

it('replaces a malformed request id instead of trusting it', function (): void {
    $response = $this->withHeader('X-Request-Id', '<script>')->getJson(route(RouteName::CurrentUser->value));

    expect(Str::isUuid($response->headers->get('X-Request-Id')))->toBeTrue();
});

it('sends hardening headers on API responses', function (): void {
    $this->getJson(route(RouteName::CurrentUser->value))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Strict-Transport-Security')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");

    expect($this->getJson(route(RouteName::CurrentUser->value))->headers->get('Cache-Control'))->toContain('no-store');
});

it('returns JSON even when the client asks for HTML', function (): void {
    $this->get(route(RouteName::CurrentUser->value), ['Accept' => 'text/html'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json');
});

it('rejects unsupported methods', function (): void {
    $this->getJson(route(RouteName::Login->value))
        ->assertStatus(405)
        ->assertJsonPath('code', 'method_not_allowed');
});

it('exposes a liveness probe', function (): void {
    $this->get('/health/live')->assertOk();
});
