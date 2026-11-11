<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Infrastructure\Context;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Meridian\Modules\Audit\Domain\Services\AuditContext;
use Meridian\Shared\Infrastructure\Logging\ContextKey;

/**
 * Reads the actor and origin from the current request and the shared log
 * context. Outside HTTP (console, queue) every value is null, meaning the
 * system acted.
 */
final readonly class RequestAuditContext implements AuditContext
{
    private const MAX_USER_AGENT_LENGTH = 512;

    public function __construct(
        private Application $app,
        private AuthFactory $auth,
    ) {}

    public function actorId(): ?string
    {
        $id = $this->auth->guard()->id();

        return $id === null ? null : (string) $id;
    }

    public function ipAddress(): ?string
    {
        return $this->request()?->ip();
    }

    public function userAgent(): ?string
    {
        $userAgent = $this->request()?->userAgent();

        return $userAgent === null ? null : mb_substr($userAgent, 0, self::MAX_USER_AGENT_LENGTH);
    }

    public function requestId(): ?string
    {
        $requestId = Context::get(ContextKey::RequestId->value);

        return is_string($requestId) ? $requestId : null;
    }

    private function request(): ?Request
    {
        return $this->app->runningInConsole() && ! $this->app->runningUnitTests()
            ? null
            : $this->app->make(Request::class);
    }
}
