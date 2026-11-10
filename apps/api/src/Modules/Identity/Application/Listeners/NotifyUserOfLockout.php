<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Listeners;

use Meridian\Modules\Identity\Domain\Events\UserLockedOut;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Infrastructure\Notifications\AccountLockedNotification;

/**
 * Tells the account owner their account was locked, so an attack against
 * them does not go unnoticed (docs/06 §3).
 */
final readonly class NotifyUserOfLockout
{
    public function __construct(private UserRepository $users) {}

    public function handle(UserLockedOut $event): void
    {
        $this->users->findById($event->userId)?->notify(new AccountLockedNotification($event->lockedUntil));
    }
}
