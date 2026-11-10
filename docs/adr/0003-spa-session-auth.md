# 0003 — Cookie Sessions (Sanctum) for the SPA Instead of JWT

- Status: Accepted
- Date: 2026-11-10

## Context

The React SPA (`app.meridian.app`) and API (`api.meridian.app`) share a parent domain. The system must support instant revocation (user disabled, password reset, admin forced logout) and must resist token theft through XSS.

## Decision

Use Laravel Sanctum's stateful SPA authentication: server-side sessions in Redis, `HttpOnly` + `Secure` + `SameSite=Lax` cookies, CSRF protection via the XSRF token. Use Sanctum personal access tokens only for non-browser integrations.

## Consequences

### Positive
- Session cookie is unreadable by JavaScript.
- Revocation is immediate (delete the Redis session).
- No refresh-token rotation logic to build and secure.

### Negative
- Requires same-site deployment and correct CORS/cookie configuration.
- Sessions are server state (Redis must be highly available).
- A future native mobile app would use tokens (or a BFF) rather than cookies.

## Alternatives considered
- **JWT in `localStorage`:** exposed to XSS; revocation needs a deny-list.
- **JWT in HttpOnly cookie + refresh tokens:** secure but more complex than sessions with no added benefit here.
- **Laravel Passport (full OAuth2 server):** unnecessary for a first-party SPA; reconsider if third parties need delegated OAuth access.
