<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Infrastructure\Persistence\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Meridian\Modules\Identity\Database\Factories\UserFactory;
use Meridian\Modules\Identity\Domain\ValueObjects\UserStatus;

/**
 * A login account. Not every employee has one, and not every user is an
 * employee (docs/04 §6).
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property UserStatus $status
 * @property CarbonImmutable|null $email_verified_at
 * @property int $failed_login_count
 * @property CarbonImmutable|null $locked_until
 * @property CarbonImmutable|null $last_login_at
 * @property CarbonImmutable|null $password_changed_at
 * @property int $token_version
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;

    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password', 'status'];

    /**
     * @var list<string>
     */
    protected $hidden = ['password'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => UserStatus::Active->value,
        'failed_login_count' => 0,
        'token_version' => 0,
    ];

    public function isLockedAt(DateTimeInterface $moment): bool
    {
        return $this->locked_until !== null && $this->locked_until->greaterThan($moment);
    }

    public function canAuthenticateAt(DateTimeInterface $moment): bool
    {
        return $this->status->canAuthenticate() && ! $this->isLockedAt($moment);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * E-mail addresses are compared case-insensitively.
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: static fn (string $value): string => mb_strtolower(trim($value)));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => UserStatus::class,
            'email_verified_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'password_changed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'failed_login_count' => 'integer',
            'token_version' => 'integer',
        ];
    }
}
