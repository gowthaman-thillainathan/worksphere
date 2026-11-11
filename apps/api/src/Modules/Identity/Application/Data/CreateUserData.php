<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

use SensitiveParameter;

final readonly class CreateUserData
{
    /**
     * @param  list<string>  $roleKeys
     * @param  bool  $emailVerified  Trusted provisioning only: skips the verification e-mail.
     */
    public function __construct(
        public string $name,
        public string $email,
        #[SensitiveParameter]
        public string $password,
        public array $roleKeys = [],
        public bool $emailVerified = false,
    ) {}
}
