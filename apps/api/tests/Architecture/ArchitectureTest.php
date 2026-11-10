<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Architecture rules (docs/01 §2, docs/10 §3)
|--------------------------------------------------------------------------
*/

arch('every class declares strict types')
    ->expect(['App', 'Meridian'])
    ->toUseStrictTypes();

arch('the domain layer is framework free')
    ->expect('Meridian\Modules\*\Domain')
    ->not->toUse(['Illuminate', 'Laravel', 'Firebase']);

arch('the shared domain kernel is framework free')
    ->expect('Meridian\Shared\Domain')
    ->not->toUse(['Illuminate', 'Laravel']);

arch('the Identity module only uses the public contracts of Access')
    ->expect('Meridian\Modules\Identity')
    ->not->toUse([
        'Meridian\Modules\Access\Domain',
        'Meridian\Modules\Access\Application',
        'Meridian\Modules\Access\Infrastructure',
        'Meridian\Modules\Access\Http',
    ]);

arch('the Access module does not depend on Identity')
    ->expect('Meridian\Modules\Access')
    ->not->toUse('Meridian\Modules\Identity');

arch('the shared kernel does not depend on modules')
    ->expect('Meridian\Shared')
    ->not->toUse('Meridian\Modules');

arch('controllers do not touch the database directly')
    ->expect('Meridian\Modules\*\Http\Controllers')
    ->not->toUse(['Illuminate\Support\Facades\DB', 'Illuminate\Database']);

arch('domain exceptions extend the shared base class')
    ->expect('Meridian\Modules\*\Domain\Exceptions')
    ->classes()
    ->toExtend('Meridian\Shared\Domain\Exceptions\DomainException');

arch('no debugging statements are left behind')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();
