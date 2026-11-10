<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Meridian\Modules\Identity\Application\Data\ResetPasswordData;

final class ResetPasswordRequest extends FormRequest
{
    private const MAX_EMAIL_LENGTH = 255;

    private const MAX_TOKEN_LENGTH = 255;

    /**
     * @return array<string, list<string|ValidationRule|Password>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:'.self::MAX_TOKEN_LENGTH],
            'email' => ['required', 'string', 'email:rfc', 'max:'.self::MAX_EMAIL_LENGTH],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function toData(): ResetPasswordData
    {
        return new ResetPasswordData(
            email: $this->string('email')->toString(),
            token: $this->string('token')->toString(),
            password: $this->string('password')->toString(),
        );
    }
}
