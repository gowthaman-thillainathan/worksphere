<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Domain\Services;

use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventData;

/**
 * Tamper evidence: hash = SHA-256(previous hash ‖ canonical JSON of the event).
 *
 * Editing, deleting or reordering any event breaks every hash after it.
 */
final class AuditChainHasher
{
    private const ALGORITHM = 'sha256';

    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    public static function hash(?string $previousHash, AuditEventData $event): string
    {
        return hash(self::ALGORITHM, ($previousHash ?? '').self::canonicalJson($event->toHashPayload()));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function canonicalJson(array $payload): string
    {
        return json_encode(self::sortKeys($payload), self::JSON_FLAGS);
    }

    private static function sortKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::sortKeys(...), $value);
    }
}
