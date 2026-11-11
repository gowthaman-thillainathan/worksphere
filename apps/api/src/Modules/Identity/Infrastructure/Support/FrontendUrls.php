<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Support;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * Links into the React SPA used in e-mails and redirects.
 */
final readonly class FrontendUrls
{
    public function __construct(private Config $config) {}

    public function resetPassword(string $token, string $email): string
    {
        return $this->url('reset_password').'?'.http_build_query(['token' => $token, 'email' => $email]);
    }

    /**
     * The reset page in "set your password" mode, for invited accounts.
     */
    public function acceptInvitation(string $token, string $email): string
    {
        return $this->url('reset_password').'?'.http_build_query(['token' => $token, 'email' => $email, 'invited' => 1]);
    }

    public function forgotPassword(): string
    {
        return $this->url('forgot_password');
    }

    public function emailVerified(): string
    {
        return $this->url('email_verified');
    }

    public function emailVerificationFailed(): string
    {
        return $this->url('email_verification_failed');
    }

    private function url(string $page): string
    {
        return $this->config->get('api.frontend_url').$this->config->get('identity.frontend_paths.'.$page);
    }
}
