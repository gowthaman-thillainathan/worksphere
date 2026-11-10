<?php

declare(strict_types=1);

use Meridian\Modules\Identity\Domain\ValueObjects\RefreshTokenSecret;

it('generates URL-safe tokens with the requested entropy', function (): void {
    $secret = RefreshTokenSecret::generate(64);

    expect($secret->plain())->toMatch('/^[A-Za-z0-9_-]+$/')
        ->and(strlen($secret->plain()))->toBe(86);
});

it('never generates the same token twice', function (): void {
    expect(RefreshTokenSecret::generate(64)->plain())->not->toBe(RefreshTokenSecret::generate(64)->plain());
});

it('hashes deterministically with SHA-256 and never returns the plain value', function (): void {
    $secret = RefreshTokenSecret::fromPlain('opaque-token');

    expect($secret->hash())->toBe(hash('sha256', 'opaque-token'))
        ->and($secret->hash())->toHaveLength(64)
        ->and($secret->hash())->not->toBe($secret->plain());
});
