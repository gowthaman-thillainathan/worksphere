<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Meridian\Modules\Identity\Application\Actions\SendEmailVerification;
use Meridian\Modules\Identity\Application\Actions\VerifyEmail;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;
use Meridian\Modules\Identity\Infrastructure\Support\FrontendUrls;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

final readonly class EmailVerificationController
{
    public function __construct(private FrontendUrls $frontend) {}

    /**
     * GET /auth/email/verify/{id}/{hash} — the signed link from the e-mail.
     *
     * Opened in a browser, so it redirects to the SPA instead of returning JSON.
     */
    public function verify(Request $request, VerifyEmail $verifyEmail, string $id, string $hash): RedirectResponse
    {
        $verified = $request->hasValidSignature() && $verifyEmail->handle($id, $hash);

        return redirect()->away($verified ? $this->frontend->emailVerified() : $this->frontend->emailVerificationFailed());
    }

    /**
     * POST /auth/email/verification-notification — re-sends the link.
     */
    public function resend(Request $request, SendEmailVerification $sendEmailVerification): Response
    {
        /** @var User $user */
        $user = $request->user();
        $sendEmailVerification->handle($user);

        return response()->noContent(HttpStatus::HTTP_ACCEPTED);
    }
}
