<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Meridian\Modules\Identity\Application\Data\AuthenticatedSession;

/**
 * The access token is returned in the body; the refresh token travels only in
 * its HttpOnly cookie and never appears here.
 *
 * @property AuthenticatedSession $resource
 */
final class AccessTokenResource extends JsonResource
{
    private const TOKEN_TYPE = 'Bearer';

    public function __construct(AuthenticatedSession $session)
    {
        parent::__construct($session);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tokens = $this->resource->tokens;

        return [
            'access_token' => $tokens->accessToken,
            'token_type' => self::TOKEN_TYPE,
            'expires_in' => $tokens->accessTokenLifetime(CarbonImmutable::now()),
            'expires_at' => CarbonImmutable::instance($tokens->accessTokenExpiresAt)->toIso8601ZuluString(),
            'user' => new UserResource($this->resource->user),
        ];
    }
}
