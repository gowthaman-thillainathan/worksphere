<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Data;

use SensitiveParameter;

final readonly class ResetPasswordData
{
    public function __construct(
        public string $email,
        #[SensitiveParameter]
        public string $token,
        #[SensitiveParameter]
        public string $password,
    ) {}
}
