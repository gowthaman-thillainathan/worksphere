<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http;

enum RouteName: string
{
    case Login = 'identity.login';
    case Refresh = 'identity.refresh';
    case Logout = 'identity.logout';
    case LogoutEverywhere = 'identity.logout-everywhere';
    case ForgotPassword = 'identity.password.forgot';
    case ResetPassword = 'identity.password.reset';
    case VerifyEmail = 'identity.email.verify';
    case ResendEmailVerification = 'identity.email.resend';
    case CurrentUser = 'identity.me';
}
