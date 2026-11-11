<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;

function openApiDocument(): string
{
    return (string) file_get_contents((string) config('api.docs.spec_path'));
}

it('serves Swagger UI and the OpenAPI document', function (): void {
    $assets = 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@'.config('api.docs.swagger_ui_version');

    $this->get(route('docs.ui'))
        ->assertOk()
        ->assertSee($assets.'/swagger-ui-bundle.js', escape: false)
        ->assertSee($assets.'/swagger-ui.css', escape: false)
        ->assertDontSee('{{', escape: false);

    $this->get(route('docs.spec'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/yaml');
});

it('documents every API route in the OpenAPI contract', function (): void {
    $prefix = trim((string) config('api.prefix'), '/').'/';
    $document = openApiDocument();

    $undocumented = collect(Router::getRoutes()->getRoutes())
        ->filter(static fn (Route $route): bool => str_starts_with($route->uri(), $prefix))
        ->map(static fn (Route $route): string => '/'.substr($route->uri(), strlen($prefix)))
        ->unique()
        ->reject(static fn (string $path): bool => str_contains($document, "\n  {$path}:\n"))
        ->values()
        ->all();

    expect($undocumented)->toBe([]);
});
