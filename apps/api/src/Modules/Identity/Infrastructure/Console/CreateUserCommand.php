<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\Rules\Password;
use Meridian\Modules\Identity\Application\Actions\CreateUser;
use Meridian\Modules\Identity\Application\Data\CreateUserData;
use Meridian\Shared\Domain\Exceptions\DomainException;

/**
 * Provisions an account from the command line, e.g. the first tenant admin:
 *   php artisan identity:create-user admin@example.com --name="Ada Admin" --role=tenant_admin
 */
final class CreateUserCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'identity:create-user
        {email : E-mail address (used to sign in)}
        {--name= : Display name}
        {--role=* : Role key(s) to grant, e.g. tenant_admin}';

    /**
     * @var string
     */
    protected $description = 'Create a user account and send its e-mail verification link';

    public function handle(CreateUser $createUser, ValidatorFactory $validators): int
    {
        $input = [
            'email' => (string) $this->argument('email'),
            'name' => (string) ($this->option('name') ?: $this->ask('Name')),
            'password' => (string) $this->secret('Password'),
        ];

        $validator = $validators->make($input, [
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::INVALID;
        }

        try {
            /** @var list<string> $roles */
            $roles = $this->option('role');
            $user = $createUser->handle(new CreateUserData($input['name'], $input['email'], $input['password'], $roles));
        } catch (DomainException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Created user %s (%s). A verification e-mail has been sent.', $user->email, $user->id));

        return self::SUCCESS;
    }
}
