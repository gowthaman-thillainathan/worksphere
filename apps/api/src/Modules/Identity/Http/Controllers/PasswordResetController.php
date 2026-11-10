<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Controllers;

use Illuminate\Http\Response;
use Meridian\Modules\Identity\Application\Actions\ResetPassword;
use Meridian\Modules\Identity\Application\Actions\SendPasswordResetLink;
use Meridian\Modules\Identity\Http\Requests\ForgotPasswordRequest;
use Meridian\Modules\Identity\Http\Requests\ResetPasswordRequest;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

final readonly class PasswordResetController
{
    /**
     * POST /auth/forgot-password — always 202, whether or not the account exists.
     */
    public function forgot(ForgotPasswordRequest $request, SendPasswordResetLink $sendPasswordResetLink): Response
    {
        $sendPasswordResetLink->handle($request->email());

        return response()->noContent(HttpStatus::HTTP_ACCEPTED);
    }

    /**
     * POST /auth/reset-password
     */
    public function reset(ResetPasswordRequest $request, ResetPassword $resetPassword): Response
    {
        $resetPassword->handle($request->toData());

        return response()->noContent();
    }
}
