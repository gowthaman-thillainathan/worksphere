<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Database\ConnectionInterface;
use LogicException;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Identity\Contracts\AccountProvisioner;
use Meridian\Modules\Identity\Contracts\Data\AccountSummary;
use Meridian\Modules\Identity\Domain\Exceptions\EmailAlreadyTaken;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Domain\ValueObjects\UserStatus;
use Meridian\Modules\Identity\Infrastructure\Notifications\AccountInvitationNotification;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

/**
 * Invitation-based provisioning: the account starts without a password; the
 * invitation link is a password reset token, so the person chooses their own
 * password and proves they own the address in one step.
 */
final readonly class ProvisionAccount implements AccountProvisioner
{
    public function __construct(
        private UserRepository $users,
        private RoleAssigner $roles,
        private PasswordBrokerFactory $passwordBrokers,
        private ConnectionInterface $database,
    ) {}

    public function invite(string $name, string $email, string $roleKey, ?string $invitedBy = null): AccountSummary
    {
        if ($this->users->emailExists($email)) {
            throw new EmailAlreadyTaken;
        }

        return $this->database->transaction(function () use ($name, $email, $roleKey, $invitedBy): AccountSummary {
            $user = new User([
                'name' => $name,
                'email' => $email,
                'status' => UserStatus::Active,
            ]);
            $this->users->save($user);
            $this->roles->replace($user->id, $roleKey, $invitedBy);

            // Queued after commit: no invitation for an account that was rolled back.
            $user->notify(new AccountInvitationNotification($this->invitationToken($user)));

            return $this->summarise($user);
        });
    }

    public function changeRole(string $userId, string $roleKey, ?string $changedBy = null): AccountSummary
    {
        $user = $this->users->findById($userId) ?? throw new LogicException(sprintf('Account %s does not exist.', $userId));

        $this->roles->replace($user->id, $roleKey, $changedBy);

        return $this->summarise($user);
    }

    public function find(string $userId): ?AccountSummary
    {
        $user = $this->users->findById($userId);

        return $user === null ? null : $this->summarise($user);
    }

    /**
     * A password reset token: single-use, hashed at rest, expires like any reset link.
     */
    private function invitationToken(User $user): string
    {
        $broker = $this->passwordBrokers->broker();

        if (! $broker instanceof PasswordBroker) {
            throw new LogicException('Invitations need the framework password broker to issue tokens.');
        }

        return $broker->createToken($user);
    }

    private function summarise(User $user): AccountSummary
    {
        return new AccountSummary(
            id: $user->id,
            email: $user->email,
            status: $user->status->value,
            emailVerified: $user->email_verified_at !== null,
            roles: $this->roles->rolesOf($user->id),
            lastLoginAt: $user->last_login_at,
            createdAt: $user->created_at,
        );
    }
}
