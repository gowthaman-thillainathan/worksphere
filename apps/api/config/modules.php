<?php

declare(strict_types=1);

use Meridian\Modules\Access\AccessServiceProvider;
use Meridian\Modules\Identity\IdentityServiceProvider;

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
    ],

];
