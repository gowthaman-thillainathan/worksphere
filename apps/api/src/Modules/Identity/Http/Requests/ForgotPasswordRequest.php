<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    private const MAX_EMAIL_LENGTH = 255;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:'.self::MAX_EMAIL_LENGTH],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }
}
