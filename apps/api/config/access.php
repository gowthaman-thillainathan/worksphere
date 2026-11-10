<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Effective-permission cache
    |--------------------------------------------------------------------------
    |
    | A user's compiled permission set is cached for this many seconds and is
    | invalidated immediately whenever their role assignments change.
    |
    */

    'cache_ttl_seconds' => (int) env('ACCESS_CACHE_TTL', 600),

];
