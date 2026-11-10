<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Access\Contracts\Exceptions\RoleNotFound;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Identity\Application\Data\CreateUserData;
use Meridian\Modules\Identity\Domain\Exceptions\EmailAlreadyTaken;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Domain\ValueObjects\UserStatus;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

/**
 * Provisions an account, grants its roles and sends the e-mail verification
 * link. Used by the bootstrap command now and the user-invite API later.
 */
final readonly class CreateUser
{
    public function __construct(
        private UserRepository $users,
        private RoleAssigner $roles,
        private SendEmailVerification $sendEmailVerification,
        private ConnectionInterface $database,
    ) {}

    /**
     * @throws EmailAlreadyTaken
     * @throws RoleNotFound
     */
    public function handle(CreateUserData $data): User
    {
        if ($this->users->emailExists($data->email)) {
            throw new EmailAlreadyTaken;
        }

        $user = $this->database->transaction(function () use ($data): User {
            $user = new User([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'status' => UserStatus::Active,
            ]);
            $user->password_changed_at = CarbonImmutable::now();
            $this->users->save($user);

            foreach ($data->roleKeys as $roleKey) {
                $this->roles->assign($user->id, $roleKey);
            }

            return $user;
        });

        $this->sendEmailVerification->handle($user);

        return $user;
    }
}
