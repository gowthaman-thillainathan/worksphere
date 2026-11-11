<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Responses;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Domain\Exceptions\ErrorCategory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Converts every exception raised while serving the API into a consistent
 * RFC 9457 problem response. Internal details never reach the client; they
 * are reported to the logs and correlated through the request id.
 */
final readonly class ApiExceptionRenderer
{
    private const DETAIL_UNAUTHENTICATED = 'Authentication is required to access this resource.';

    private const DETAIL_VALIDATION = 'The given data was invalid.';

    private const DETAIL_INTERNAL = 'An unexpected error occurred. Quote the request_id when contacting support.';

    public function __construct(private Config $config) {}

    public function render(Throwable $exception, Request $request): Response
    {
        if ($exception instanceof HttpResponseException) {
            return $exception->getResponse();
        }

        return $this->toProblem($exception)->toResponse($request);
    }

    private function toProblem(Throwable $exception): ProblemDetails
    {
        return match (true) {
            $exception instanceof ValidationException => $this->problem(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                code: CommonErrorCode::ValidationFailed->value,
                detail: self::DETAIL_VALIDATION,
                errors: $this->validationErrors($exception),
            ),
            $exception instanceof DomainException => $this->problem(
                status: $this->statusFor($exception->category()),
                code: $exception->errorCode(),
                detail: $exception->getMessage(),
            ),
            $exception instanceof AuthenticationException => $this->problem(
                status: Response::HTTP_UNAUTHORIZED,
                code: CommonErrorCode::Unauthenticated->value,
                detail: self::DETAIL_UNAUTHENTICATED,
                headers: ['WWW-Authenticate' => 'Bearer'],
            ),
            $exception instanceof HttpExceptionInterface => $this->problem(
                status: $exception->getStatusCode(),
                code: CommonErrorCode::fromStatus($exception->getStatusCode())->value,
                detail: $this->httpDetail($exception),
                headers: $exception->getHeaders(),
            ),
            default => $this->problem(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: CommonErrorCode::InternalError->value,
                detail: self::DETAIL_INTERNAL,
            ),
        };
    }

    /**
     * @param  array<string, list<array{code: string, message: string}>>  $errors
     * @param  array<string, string>  $headers
     */
    private function problem(int $status, string $code, string $detail, array $errors = [], array $headers = []): ProblemDetails
    {
        return new ProblemDetails(
            status: $status,
            code: $code,
            detail: $detail,
            typeBaseUrl: (string) $this->config->get('api.errors.type_base_url'),
            errors: $errors,
            headers: $headers,
        );
    }

    private function statusFor(ErrorCategory $category): int
    {
        return match ($category) {
            ErrorCategory::InvalidInput => Response::HTTP_UNPROCESSABLE_ENTITY,
            ErrorCategory::Unauthenticated => Response::HTTP_UNAUTHORIZED,
            ErrorCategory::Forbidden => Response::HTTP_FORBIDDEN,
            ErrorCategory::NotFound => Response::HTTP_NOT_FOUND,
            ErrorCategory::Conflict => Response::HTTP_CONFLICT,
            ErrorCategory::PreconditionFailed => Response::HTTP_PRECONDITION_FAILED,
        };
    }

    /**
     * Pairs every failed rule with its message: {field: [{code, message}]}.
     *
     * @return array<string, list<array{code: string, message: string}>>
     */
    private function validationErrors(ValidationException $exception): array
    {
        $failedRules = $exception->validator->failed();
        $errors = [];

        foreach ($exception->errors() as $field => $messages) {
            $ruleNames = array_keys($failedRules[$field] ?? []);

            foreach (array_values($messages) as $index => $message) {
                $rule = $ruleNames[$index] ?? CommonErrorCode::ValidationFailed->value;
                $errors[$field][] = [
                    'code' => Str::snake(class_basename($rule)),
                    'message' => $message,
                ];
            }
        }

        return $errors;
    }

    /**
     * Framework HTTP exceptions (404, 405, 429...) carry developer-oriented
     * messages; only the standard reason phrase is exposed.
     */
    private function httpDetail(HttpExceptionInterface $exception): string
    {
        return Response::$statusTexts[$exception->getStatusCode()] ?? CommonErrorCode::HttpError->value;
    }
}
