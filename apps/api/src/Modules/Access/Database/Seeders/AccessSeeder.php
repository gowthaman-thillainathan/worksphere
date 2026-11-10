<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Database\Seeders;

use Illuminate\Database\Seeder;
use Meridian\Modules\Access\Application\Actions\SyncPermissions;

final class AccessSeeder extends Seeder
{
    public function run(SyncPermissions $syncPermissions): void
    {
        $syncPermissions->handle();
    }
}
