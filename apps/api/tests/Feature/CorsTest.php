<?php

declare(strict_types=1);

use Meridian\Modules\Identity\Http\RouteName;

it('allows the SPA origin with credentials and exposes the ETag', function (): void {
    $this->withHeaders([
        'Origin' => 'https://app.meridian.test',
        'Access-Control-Request-Method' => 'PATCH',
        'Access-Control-Request-Headers' => 'authorization,if-match,content-type',
    ])->options(route(RouteName::CurrentUser->value))
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://app.meridian.test')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');

    $response = $this->withHeader('Origin', 'https://app.meridian.test')->getJson(route(RouteName::CurrentUser->value));

    expect($response->headers->get('Access-Control-Expose-Headers'))->toContain('ETag');
});

it('never grants other origins', function (): void {
    $response = $this->withHeader('Origin', 'https://evil.example')->getJson(route(RouteName::CurrentUser->value));

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.example')
        ->and($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('*');
});
