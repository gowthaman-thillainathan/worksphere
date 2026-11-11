<?php

declare(strict_types=1);

use Meridian\Modules\Access\AccessServiceProvider;
use Meridian\Modules\Audit\AuditServiceProvider;
use Meridian\Modules\Employee\EmployeeServiceProvider;
use Meridian\Modules\Identity\IdentityServiceProvider;
use Meridian\Modules\Organisation\OrganisationServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled modules
    |--------------------------------------------------------------------------
    |
    | Each bounded context registers itself through its own service provider
    | (bindings, routes, migrations, listeners, commands). See docs/02 §2.1.
    |
    */

    'providers' => [
        IdentityServiceProvider::class,
        AccessServiceProvider::class,
        AuditServiceProvider::class,
        OrganisationServiceProvider::class,
        EmployeeServiceProvider::class,
    ],

];
