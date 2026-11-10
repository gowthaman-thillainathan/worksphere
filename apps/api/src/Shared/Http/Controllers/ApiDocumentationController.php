<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Controllers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves the OpenAPI contract and a Swagger UI page to explore it.
 * Routes are registered only when api.docs.enabled is true.
 */
final readonly class ApiDocumentationController
{
    private const SPEC_CONTENT_TYPE = 'application/yaml';

    public function __construct(
        private Config $config,
        private ViewFactory $views,
    ) {}

    public function ui(): View
    {
        return $this->views->make('api-docs', [
            'specUrl' => route('docs.spec'),
            'swaggerUiVersion' => $this->config->get('api.docs.swagger_ui_version'),
        ]);
    }

    public function spec(): BinaryFileResponse
    {
        $path = (string) $this->config->get('api.docs.spec_path');

        if (! is_file($path)) {
            throw new NotFoundHttpException;
        }

        return new BinaryFileResponse($path, headers: ['Content-Type' => self::SPEC_CONTENT_TYPE]);
    }
}
