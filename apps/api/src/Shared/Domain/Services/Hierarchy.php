<?php

declare(strict_types=1);

namespace Meridian\Shared\Domain\Services;

/**
 * Cycle detection for self-referencing trees (reporting lines, department
 * hierarchy): attaching a node under a new parent creates a cycle when the
 * node is that parent or one of the parent's ancestors.
 */
final class Hierarchy
{
    /**
     * Guards against corrupted data turning the walk into an endless loop.
     */
    private const MAX_DEPTH = 100;

    /**
     * @param  callable(string): ?string  $parentOf  Returns the parent id of a node, or null at the root.
     */
    public static function wouldCreateCycle(string $nodeId, string $newParentId, callable $parentOf): bool
    {
        $current = $newParentId;

        for ($depth = 0; $depth < self::MAX_DEPTH; $depth++) {
            if ($current === $nodeId) {
                return true;
            }

            $current = $parentOf($current);

            if ($current === null) {
                return false;
            }
        }

        return true;
    }
}
