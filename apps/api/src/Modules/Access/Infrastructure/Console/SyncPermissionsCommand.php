<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Infrastructure\Console;

use Illuminate\Console\Command;
use Meridian\Modules\Access\Application\Actions\SyncPermissions;

final class SyncPermissionsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'access:sync-permissions';

    /**
     * @var string
     */
    protected $description = 'Mirror the permission catalogue and system roles from code into the database';

    public function handle(SyncPermissions $syncPermissions): int
    {
        $result = $syncPermissions->handle();

        $this->components->info(sprintf(
            'Synced %d permissions (%d removed) and %d system roles.',
            $result->permissions,
            $result->removedPermissions,
            $result->systemRoles,
        ));

        return self::SUCCESS;
    }
}
