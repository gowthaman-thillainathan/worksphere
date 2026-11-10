<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Meridian\Modules\Identity\Domain\ValueObjects\UserStatus;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * Plain-text password of every factory user. Test data only.
     */
    public const PASSWORD = 'correct-horse-battery-staple';

    /**
     * @var class-string<User>
     */
    protected $model = User::class;

    private static ?string $passwordHash = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => CarbonImmutable::now(),
            'password' => self::$passwordHash ??= Hash::make(self::PASSWORD),
            'status' => UserStatus::Active,
            'password_changed_at' => CarbonImmutable::now(),
        ];
    }

    public function unverified(): self
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function disabled(): self
    {
        return $this->state(['status' => UserStatus::Disabled]);
    }

    public function lockedUntil(CarbonImmutable $until): self
    {
        return $this->state(['locked_until' => $until]);
    }
}
