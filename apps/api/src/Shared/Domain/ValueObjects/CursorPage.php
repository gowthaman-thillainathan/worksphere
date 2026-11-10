<?php

declare(strict_types=1);

namespace Meridian\Shared\Domain\ValueObjects;

/**
 * One page of a cursor-paginated result (docs/05 §3). Cursors are opaque to
 * clients and stable under concurrent inserts.
 *
 * @template TItem
 */
final readonly class CursorPage
{
    /**
     * @param  list<TItem>  $items
     */
    public function __construct(
        public array $items,
        public int $perPage,
        public ?string $nextCursor,
        public ?string $previousCursor,
    ) {}
}
