<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;

function openApiDocument(): string
{
    return (string) file_get_contents((string) config('api.docs.spec_path'));
}

it('serves Swagger UI and the OpenAPI document', function (): void {
    $this->get(route('docs.ui'))->assertOk()->assertSee('swagger-ui', escape: false);

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
