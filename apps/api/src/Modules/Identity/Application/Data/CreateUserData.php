<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

use SensitiveParameter;

final readonly class CreateUserData
{
    /**
     * @param  list<string>  $roleKeys
     */
    public function __construct(
        public string $name,
        public string $email,
        #[SensitiveParameter]
        public string $password,
        public array $roleKeys = [],
    ) {}
}
