<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Meridian\Shared\Infrastructure\Logging\ContextKey;
use Symfony\Component\HttpFoundation\Response;

/**
 * An RFC 9457 "application/problem+json" error response.
 *
 * Shape: type, title, status, code, detail, instance, request_id and, for
 * validation failures, errors keyed by field.
 */
final readonly class ProblemDetails implements Responsable
{
    public const CONTENT_TYPE = 'application/problem+json';

    /**
     * @param  array<string, list<array{code: string, message: string}>>  $errors
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public int $status,
        public string $code,
        public string $detail,
        public string $typeBaseUrl,
        public array $errors = [],
        public array $headers = [],
    ) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse
    {
        $body = [
            'type' => $this->typeBaseUrl.'/'.$this->code,
            'title' => Response::$statusTexts[$this->status] ?? 'Error',
            'status' => $this->status,
            'code' => $this->code,
            'detail' => $this->detail,
            'instance' => $request->getPathInfo(),
            'request_id' => Context::get(ContextKey::RequestId->value),
        ];

        if ($this->errors !== []) {
            $body['errors'] = $this->errors;
        }

        return new JsonResponse(
            data: $body,
            status: $this->status,
            headers: [...$this->headers, 'Content-Type' => self::CONTENT_TYPE],
        );
    }
}
