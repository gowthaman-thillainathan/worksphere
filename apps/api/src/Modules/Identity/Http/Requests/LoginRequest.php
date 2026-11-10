<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Meridian\Modules\Identity\Application\Data\LoginData;
use Meridian\Modules\Identity\Http\Support\ClientContextFactory;

final class LoginRequest extends FormRequest
{
    private const MAX_EMAIL_LENGTH = 255;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:'.self::MAX_EMAIL_LENGTH],
            'password' => ['required', 'string', 'max:'.config('identity.password.max_length')],
        ];
    }

    public function toData(): LoginData
    {
        return new LoginData(
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
            client: ClientContextFactory::fromRequest($this),
        );
    }
}
