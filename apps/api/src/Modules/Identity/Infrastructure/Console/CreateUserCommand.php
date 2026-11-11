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
 *
 * Unattended (CI, provisioning scripts) — the password never appears in argv:
 *   printf '%s' "$PASSWORD" | php artisan identity:create-user e2e@example.com --name=E2E --password-stdin --verified
 */
final class CreateUserCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'identity:create-user
        {email : E-mail address (used to sign in)}
        {--name= : Display name}
        {--role=* : Role key(s) to grant, e.g. tenant_admin}
        {--password-stdin : Read the password from standard input instead of prompting}
        {--verified : Mark the e-mail address as verified (trusted provisioning only)}';

    /**
     * @var string
     */
    protected $description = 'Create a user account and send its e-mail verification link';

    public function handle(CreateUser $createUser, ValidatorFactory $validators): int
    {
        $input = [
            'email' => (string) $this->argument('email'),
            'name' => (string) ($this->option('name') ?: $this->ask('Name')),
            'password' => $this->option('password-stdin') ? $this->passwordFromStdin() : (string) $this->secret('Password'),
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
            $user = $createUser->handle(new CreateUserData(
                name: $input['name'],
                email: $input['email'],
                password: $input['password'],
                roleKeys: $roles,
                emailVerified: (bool) $this->option('verified'),
            ));
        } catch (DomainException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->components->info(sprintf('Created verified user %s (%s).', $user->email, $user->id));

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Created user %s (%s). A verification e-mail has been sent.', $user->email, $user->id));

        return self::SUCCESS;
    }

    private function passwordFromStdin(): string
    {
        return rtrim((string) stream_get_contents(STDIN), "\r\n");
    }
}
