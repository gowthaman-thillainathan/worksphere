<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Concurrency;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Version-based ETags for optimistic locking (docs/05 §2).
 *
 * Responses carry `ETag: "v7"`; writes must send `If-Match: "v7"`. A missing
 * header is 428 Precondition Required, a malformed one 412.
 */
final class EntityTag
{
    public const RESPONSE_HEADER = 'ETag';

    public const REQUEST_HEADER = 'If-Match';

    private const PATTERN = '/^(?:W\/)?"v(\d+)"$/';

    private const FORMAT = '"v%d"';

    public static function forVersion(int $version): string
    {
        return sprintf(self::FORMAT, $version);
    }

    /**
     * @throws HttpException when the header is missing (428) or malformed (412)
     */
    public static function requiredVersion(Request $request): int
    {
        $header = trim((string) $request->headers->get(self::REQUEST_HEADER, ''));

        if ($header === '') {
            throw new HttpException(Response::HTTP_PRECONDITION_REQUIRED, 'If-Match header is required.');
        }

        if (preg_match(self::PATTERN, $header, $matches) !== 1) {
            throw new PreconditionFailedHttpException('If-Match header is malformed.');
        }

        return (int) $matches[1];
    }
}
