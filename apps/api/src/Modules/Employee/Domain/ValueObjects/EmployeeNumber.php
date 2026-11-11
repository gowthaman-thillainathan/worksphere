<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Human-friendly, immutable employee identifier, e.g. EMP-000421.
 */
final readonly class EmployeeNumber
{
    private const PAD_CHARACTER = '0';

    private function __construct(public string $value) {}

    public static function fromSequence(int $sequence, string $prefix, int $padding): self
    {
        if ($sequence < 1) {
            throw new InvalidArgumentException('Employee number sequence must be positive.');
        }

        return new self($prefix.str_pad((string) $sequence, $padding, self::PAD_CHARACTER, STR_PAD_LEFT));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
