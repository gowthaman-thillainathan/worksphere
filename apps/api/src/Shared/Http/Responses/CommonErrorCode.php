<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Responses;

use Symfony\Component\HttpFoundation\Response;

/**
 * Error codes for failures raised by the framework rather than by a module.
 */
enum CommonErrorCode: string
{
    case BadRequest = 'bad_request';
    case Unauthenticated = 'unauthenticated';
    case Forbidden = 'forbidden';
    case NotFound = 'not_found';
    case MethodNotAllowed = 'method_not_allowed';
    case Conflict = 'conflict';
    case PreconditionFailed = 'precondition_failed';
    case PreconditionRequired = 'precondition_required';
    case PayloadTooLarge = 'payload_too_large';
    case ValidationFailed = 'validation_failed';
    case RateLimited = 'rate_limited';
    case ServiceUnavailable = 'service_unavailable';
    case HttpError = 'http_error';
    case InternalError = 'internal_error';

    public static function fromStatus(int $status): self
    {
        return match ($status) {
            Response::HTTP_BAD_REQUEST => self::BadRequest,
            Response::HTTP_UNAUTHORIZED => self::Unauthenticated,
            Response::HTTP_FORBIDDEN => self::Forbidden,
            Response::HTTP_NOT_FOUND => self::NotFound,
            Response::HTTP_METHOD_NOT_ALLOWED => self::MethodNotAllowed,
            Response::HTTP_CONFLICT => self::Conflict,
            Response::HTTP_PRECONDITION_FAILED => self::PreconditionFailed,
            Response::HTTP_PRECONDITION_REQUIRED => self::PreconditionRequired,
            Response::HTTP_REQUEST_ENTITY_TOO_LARGE => self::PayloadTooLarge,
            Response::HTTP_UNPROCESSABLE_ENTITY => self::ValidationFailed,
            Response::HTTP_TOO_MANY_REQUESTS => self::RateLimited,
            Response::HTTP_SERVICE_UNAVAILABLE => self::ServiceUnavailable,
            default => $status >= Response::HTTP_INTERNAL_SERVER_ERROR ? self::InternalError : self::HttpError,
        };
    }
}
