<?php

declare(strict_types=1);

use Meridian\Modules\Employee\Domain\ValueObjects\EmployeeNumber;

it('pads the sequence with the configured prefix', function (): void {
    expect((string) EmployeeNumber::fromSequence(421, 'EMP-', 6))->toBe('EMP-000421')
        ->and(EmployeeNumber::fromSequence(1234567, 'EMP-', 6)->value)->toBe('EMP-1234567');
});

it('rejects non-positive sequence values', function (): void {
    EmployeeNumber::fromSequence(0, 'EMP-', 6);
})->throws(InvalidArgumentException::class);
