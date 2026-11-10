<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Domain\Events;

enum LoginFailureReason: string
{
    case UnknownAccount = 'unknown_account';
    case WrongPassword = 'wrong_password';
    case AccountLocked = 'account_locked';
    case AccountDisabled = 'account_disabled';
}
