<?php

declare(strict_types=1);

use Meridian\Modules\Audit\Contracts\ChangeSet;

it('records only fields that changed, as [old, new] pairs sorted by field', function (): void {
    $changes = ChangeSet::between(
        ['last_name' => 'Perera', 'job_title' => 'Engineer', 'status' => 'active'],
        ['last_name' => 'Perera', 'job_title' => 'Lead Engineer', 'status' => 'on_leave'],
    );

    expect($changes)->toBe([
        'job_title' => ['Engineer', 'Lead Engineer'],
        'status' => ['active', 'on_leave'],
    ]);
});

it('masks sensitive values but still records that they changed', function (): void {
    $changes = ChangeSet::between(['phone' => null], ['phone' => '+94 77 123 4567'], ['phone']);

    expect($changes)->toBe(['phone' => [null, ChangeSet::MASK]]);
});

it('describes a creation as changes from nothing', function (): void {
    expect(ChangeSet::created(['first_name' => 'Nimal', 'preferred_name' => null]))
        ->toBe(['first_name' => [null, 'Nimal']]);
});
