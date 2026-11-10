<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

use SensitiveParameter;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        #[SensitiveParameter]
        public string $password,
        public ClientContext $client,
    ) {}
}
