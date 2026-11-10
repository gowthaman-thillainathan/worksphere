<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http;

enum RateLimiterName: string
{
    case Login = 'identity.login';
    case PasswordReset = 'identity.password-reset';
    case Refresh = 'identity.refresh';
    case EmailVerification = 'identity.email-verification';
}
