# Identity Module

Accounts, credentials, tokens and sessions. Design: [docs/06](../../../../../docs/06-authentication.md), [ADR-0006](../../../../../docs/adr/0006-jwt-access-and-rotating-refresh-tokens.md).

**Style:** pragmatic ([ADR-0004](../../../../../docs/adr/0004-pragmatic-ddd.md)). The Eloquent `User` and `RefreshToken` models are the persistence model. The framework-free `Domain` layer holds the rules and ports: value objects, events, exceptions, and the repository and token-codec interfaces.

| Layer | Contents |
|---|---|
| `Domain` | `AccessTokenClaims`, `RefreshTokenSecret`, `EmailVerificationHash`, `UserStatus`; `AccessTokenCodec` / `AccessTokenRevocationList` ports; security events; domain exceptions |
| `Application` | One action per use case (`LoginUser`, `RefreshAccessToken`, `LogoutUser`, `LogoutEverywhere`, `SendPasswordResetLink`, `ResetPassword`, `VerifyEmail`, `CreateUser`…), DTOs, listeners |
| `Infrastructure` | Eloquent models and repositories, `FirebaseJwtAccessTokenCodec`, cache-backed revocation list, `BearerTokenAuthenticator` (the `jwt` guard), notifications, console command |
| `Http` | Thin controllers, form requests, API resources, `EnsureEmailIsVerified`, routes |

**Depends on:** `Access\Contracts` (roles and permissions for `/me` and user provisioning).
**Provides:** the `api` guard and the `authenticated` middleware group (`auth:api` + verified e-mail) that every other module uses.
