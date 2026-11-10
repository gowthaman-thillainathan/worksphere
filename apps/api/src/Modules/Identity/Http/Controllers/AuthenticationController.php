<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Meridian\Modules\Identity\Application\Actions\LoginUser;
use Meridian\Modules\Identity\Application\Actions\LogoutEverywhere;
use Meridian\Modules\Identity\Application\Actions\LogoutUser;
use Meridian\Modules\Identity\Application\Actions\RefreshAccessToken;
use Meridian\Modules\Identity\Application\Data\AuthenticatedSession;
use Meridian\Modules\Identity\Domain\Exceptions\InvalidRefreshToken;
use Meridian\Modules\Identity\Domain\ValueObjects\AccessTokenClaims;
use Meridian\Modules\Identity\Http\Requests\LoginRequest;
use Meridian\Modules\Identity\Http\Resources\AccessTokenResource;
use Meridian\Modules\Identity\Http\Support\ClientContextFactory;
use Meridian\Modules\Identity\Http\Support\RefreshTokenCookie;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

final readonly class AuthenticationController
{
    public function __construct(private RefreshTokenCookie $refreshCookie) {}

    /**
     * POST /auth/login
     */
    public function login(LoginRequest $request, LoginUser $loginUser): JsonResponse
    {
        return $this->tokenResponse($loginUser->handle($request->toData()));
    }

    /**
     * POST /auth/refresh — rotates the refresh token held in the cookie.
     */
    public function refresh(Request $request, RefreshAccessToken $refreshAccessToken): JsonResponse
    {
        $plainToken = $this->refreshCookie->read($request) ?? throw new InvalidRefreshToken;

        return $this->tokenResponse(
            $refreshAccessToken->handle($plainToken, ClientContextFactory::fromRequest($request)),
        );
    }

    /**
     * POST /auth/logout — ends the current session.
     */
    public function logout(Request $request, LogoutUser $logoutUser): Response
    {
        $logoutUser->handle($this->claims($request));

        return $this->signedOutResponse();
    }

    /**
     * POST /auth/logout-all — ends every session of the user.
     */
    public function logoutEverywhere(Request $request, LogoutEverywhere $logoutEverywhere): Response
    {
        /** @var User $user */
        $user = $request->user();
        $logoutEverywhere->handle($user, $this->claims($request));

        return $this->signedOutResponse();
    }

    private function tokenResponse(AuthenticatedSession $session): JsonResponse
    {
        return (new AccessTokenResource($session))
            ->response()
            ->withCookie($this->refreshCookie->issue($session->tokens));
    }

    private function signedOutResponse(): Response
    {
        return response()->noContent()->withCookie($this->refreshCookie->forget());
    }

    private function claims(Request $request): AccessTokenClaims
    {
        /** @var AccessTokenClaims */
        return $request->attributes->get(AccessTokenClaims::REQUEST_ATTRIBUTE);
    }
}
