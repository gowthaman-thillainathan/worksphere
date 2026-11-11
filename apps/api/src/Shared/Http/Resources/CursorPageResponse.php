<?php

declare(strict_types=1);

namespace Meridian\Shared\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Shared\Domain\ValueObjects\CursorPage;

/**
 * Renders a CursorPage in the collection shape of docs/05 §3:
 * { data: [...], meta: {per_page, next_cursor, prev_cursor}, links: {next, prev} }.
 */
final class CursorPageResponse
{
    private const CURSOR_PARAMETER = 'cursor';

    /**
     * @param  CursorPage<mixed>  $page
     * @param  class-string<JsonResource>  $resource
     */
    public static function make(CursorPage $page, string $resource, Request $request): AnonymousResourceCollection
    {
        return $resource::collection($page->items)->additional([
            'meta' => [
                'per_page' => $page->perPage,
                'next_cursor' => $page->nextCursor,
                'prev_cursor' => $page->previousCursor,
            ],
            'links' => [
                'next' => self::link($request, $page->nextCursor),
                'prev' => self::link($request, $page->previousCursor),
            ],
        ]);
    }

    private static function link(Request $request, ?string $cursor): ?string
    {
        if ($cursor === null) {
            return null;
        }

        return $request->fullUrlWithQuery([self::CURSOR_PARAMETER => $cursor]);
    }
}
