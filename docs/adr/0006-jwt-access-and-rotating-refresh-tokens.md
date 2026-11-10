# 0006 — JWT Access Tokens with Rotating Refresh Tokens

- Status: Accepted
- Date: 2026-11-10
- Supersedes: [0003 — Cookie sessions (Sanctum) for the SPA](0003-spa-session-auth.md)

## Context

ADR-0003 chose server-side cookie sessions for the React SPA. The product now needs one stateless authentication scheme for the SPA, a future mobile app and service-to-service clients. API containers should scale horizontally without shared session state on every request. Its main concerns still apply: tokens must not be readable by JavaScript, and a session must be revocable without waiting for it to expire.

## Decision

- **Access token:** a JWT signed with HS256 (`firebase/php-jwt`), valid for **15 minutes**. It is sent as `Authorization: Bearer` and stays in SPA memory only, never in `localStorage`. Claims are `iss`, `aud`, `sub`, `jti`, `sid`, `ver`, `iat`, `nbf` and `exp`. The header carries `kid` so keys can be rotated.
- **Refresh token:** an opaque 512-bit random value, stored only as a SHA-256 hash in `refresh_tokens`. It is delivered **only** in an `HttpOnly; Secure; SameSite=Strict` cookie scoped to `/api/v1/auth`, so ordinary API calls never carry it. It is valid for **14 days**.
- **Rotation:** each refresh token can be used once. `POST /auth/refresh` revokes it and issues a new one in the same *family*; the family id is the access token's `sid` claim.
- **Reuse detection:** if an already-rotated token is presented again, the whole family is revoked (OAuth 2.0 Security BCP §4.14). Either the attacker or the user loses the race, and both must sign in again.
- **Revocation:**
  - *Logout* revokes the family and deny-lists the current `jti` in the cache until it expires.
  - *Logout everywhere* and *password reset* increment `users.token_version`, which the `ver` claim must match. This invalidates every outstanding access token at once.
  - *Disabling* a user takes effect on the next request, because the guard re-reads the user.
- The JWT library sits behind the `AccessTokenCodec` domain port. The guard is registered with `Auth::viaRequest('jwt', …)`, so the rest of the application sees an ordinary Laravel guard.

## Consequences

### Positive
- Stateless verification. Only the user lookup touches the database, and that lookup is also what makes disabling a user take effect immediately.
- The refresh token cannot be read by XSS or sent cross-site. The access token is short-lived.
- Stolen refresh tokens are detected, and the session is killed.
- The same scheme works for SPA, mobile (refresh token in secure storage) and API clients.

### Negative
- More moving parts than sessions: rotation, reuse detection and a deny-list.
- An XSS can still *use* the in-memory access token for up to 15 minutes. The strict CSP and React escaping remain essential.
- Two browser tabs refreshing at the same moment race: the loser gets `401 invalid_refresh_token`. The SPA must serialise refreshes, for example with a single in-flight refresh promise.
- HS256 means the API is the only verifier. If other services need to verify tokens, switch to EdDSA or RS256 with a JWKS endpoint (a change in one class).

## Alternatives considered
- **Keep Sanctum sessions (ADR-0003):** simplest for a same-site SPA. Rejected because it doesn't serve mobile or service clients with one model.
- **Long-lived JWT without refresh tokens:** cannot be revoked in practice.
- **`tymon/jwt-auth`:** heavier, sparsely maintained, and hides the refresh-token model this design needs.
- **Laravel Passport (full OAuth2 server):** right for third-party delegated access. It's oversized for first-party clients, so we'll reconsider it if third parties need delegated access.
