<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Meridian\Modules\Identity\Application\Data\AuthenticatedSession;
use Meridian\Modules\Identity\Application\Data\ClientContext;
use Meridian\Modules\Identity\Application\Data\LockoutPolicy;
use Meridian\Modules\Identity\Application\Data\LoginData;
use Meridian\Modules\Identity\Domain\Events\LoginFailed;
use Meridian\Modules\Identity\Domain\Events\LoginFailureReason;
use Meridian\Modules\Identity\Domain\Events\UserLockedOut;
use Meridian\Modules\Identity\Domain\Events\UserLoggedIn;
use Meridian\Modules\Identity\Domain\Exceptions\InvalidCredentials;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;
use SensitiveParameter;

/**
 * Password sign-in with brute-force lockout (docs/06 §3).
 *
 * Every failure produces the same InvalidCredentials error and comparable
 * response time, so the endpoint cannot be used to discover accounts.
 */
final class LoginUser
{
    /**
     * Hash compared against when no account matches, to equalise timing.
     */
    private static ?string $timingEqualiserHash = null;

    public function __construct(
        private readonly UserRepository $users,
        private readonly IssueTokenPair $issueTokenPair,
        private readonly Hasher $hasher,
        private readonly ConnectionInterface $database,
        private readonly Dispatcher $events,
        private readonly LockoutPolicy $lockout,
    ) {}

    /**
     * @throws InvalidCredentials
     */
    public function handle(LoginData $data): AuthenticatedSession
    {
        $now = CarbonImmutable::now();
        $user = $this->users->findByEmail($data->email);
        $failure = $this->failureReason($user, $data->password, $now);

        if ($user === null || $failure !== null) {
            $this->recordFailure($user, $failure ?? LoginFailureReason::UnknownAccount, $data->client, $now);

            throw new InvalidCredentials;
        }

        $session = $this->database->transaction(function () use ($user, $data, $now): AuthenticatedSession {
            $this->completeLogin($user, $data->password, $now);

            return new AuthenticatedSession($user, $this->issueTokenPair->handle($user, $data->client));
        });

        $this->events->dispatch(new UserLoggedIn($user->id, $session->tokens->sessionId, $data->client->ipAddress));

        return $session;
    }

    private function failureReason(?User $user, #[SensitiveParameter] string $password, CarbonImmutable $now): ?LoginFailureReason
    {
        if ($user === null) {
            $this->hasher->check($password, $this->timingEqualiserHash());

            return LoginFailureReason::UnknownAccount;
        }

        $passwordMatches = $user->password !== null && $this->hasher->check($password, $user->password);

        return match (true) {
            ! $user->status->canAuthenticate() => LoginFailureReason::AccountDisabled,
            $user->isLockedAt($now) => LoginFailureReason::AccountLocked,
            ! $passwordMatches => LoginFailureReason::WrongPassword,
            default => null,
        };
    }

    private function recordFailure(?User $user, LoginFailureReason $reason, ClientContext $client, CarbonImmutable $now): void
    {
        $this->events->dispatch(new LoginFailed($user?->id, $reason, $client->ipAddress));

        if ($user === null || $reason !== LoginFailureReason::WrongPassword) {
            return;
        }

        if ($this->users->registerFailedLogin($user) < $this->lockout->maxAttempts) {
            return;
        }

        $lockedUntil = $now->addSeconds($this->lockout->durationSeconds);
        $user->forceFill(['locked_until' => $lockedUntil, 'failed_login_count' => 0]);
        $this->users->save($user);

        $this->events->dispatch(new UserLockedOut($user->id, $lockedUntil, $client->ipAddress));
    }

    private function completeLogin(User $user, #[SensitiveParameter] string $password, CarbonImmutable $now): void
    {
        $user->forceFill([
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => $now,
        ]);

        // Transparently upgrade hashes when the algorithm or cost changes.
        if ($user->password !== null && $this->hasher->needsRehash($user->password)) {
            $user->password = $password;
        }

        $this->users->save($user);
    }

    private function timingEqualiserHash(): string
    {
        return self::$timingEqualiserHash ??= $this->hasher->make(bin2hex(random_bytes(16)));
    }
}
