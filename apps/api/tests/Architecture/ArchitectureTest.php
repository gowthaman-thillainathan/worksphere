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

/*
| Modules talk to each other only through their public Contracts namespace.
*/
const MODULES = ['Access', 'Audit', 'Employee', 'Identity'];
const MODULE_INTERNALS = ['Domain', 'Application', 'Infrastructure', 'Http'];

foreach (MODULES as $module) {
    $otherModulesInternals = [];

    foreach (array_diff(MODULES, [$module]) as $other) {
        foreach (MODULE_INTERNALS as $layer) {
            $otherModulesInternals[] = "Meridian\\Modules\\{$other}\\{$layer}";
        }
    }

    arch("the {$module} module only uses other modules' contracts")
        ->expect("Meridian\\Modules\\{$module}")
        ->not->toUse($otherModulesInternals);
}

arch('the Access and Audit modules are independent of business modules')
    ->expect(['Meridian\Modules\Access', 'Meridian\Modules\Audit'])
    ->not->toUse(['Meridian\Modules\Identity', 'Meridian\Modules\Employee']);

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
