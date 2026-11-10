<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Support;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Meridian\Modules\Identity\Application\Data\TokenPair;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Transports the refresh token in an HttpOnly, Secure, SameSite=Strict
 * cookie scoped to the auth endpoints, so JavaScript (and XSS) can never
 * read it and it is not sent with ordinary API calls.
 */
final readonly class RefreshTokenCookie
{
    public function __construct(private Config $config) {}

    public function issue(TokenPair $tokens): Cookie
    {
        return $this->cookie($tokens->refreshToken->plain(), $tokens->refreshTokenExpiresAt->getTimestamp());
    }

    public function forget(): Cookie
    {
        return $this->cookie('', 1);
    }

    public function read(Request $request): ?string
    {
        $value = $request->cookies->get($this->setting('name'));

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function cookie(string $value, int $expiresAt): Cookie
    {
        return Cookie::create(
            name: $this->setting('name'),
            value: $value,
            expire: $expiresAt,
            path: $this->setting('path'),
            domain: $this->setting('domain'),
            secure: (bool) $this->setting('secure'),
            httpOnly: true,
            raw: false,
            sameSite: $this->setting('same_site'),
        );
    }

    private function setting(string $key): mixed
    {
        return $this->config->get('identity.refresh_token.cookie.'.$key);
    }
}
