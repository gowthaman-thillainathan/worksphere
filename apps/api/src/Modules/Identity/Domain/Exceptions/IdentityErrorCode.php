<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Exceptions;

enum IdentityErrorCode: string
{
    case InvalidCredentials = 'invalid_credentials';
    case InvalidAccessToken = 'invalid_access_token';
    case InvalidRefreshToken = 'invalid_refresh_token';
    case InvalidPasswordResetToken = 'invalid_password_reset_token';
    case EmailNotVerified = 'email_not_verified';
    case EmailAlreadyTaken = 'email_already_taken';
}
