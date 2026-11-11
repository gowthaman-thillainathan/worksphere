# 05 — API Structure

A RESTful JSON API, described by OpenAPI 3.1 in `packages/api-contract/openapi.yaml`. The spec is the contract: frontend types, request validation tests and published docs (Redoc / Scalar) are all generated from it.

## 1. Base URL and Versioning

```text
https://api.meridian.app/api/v1/...
```

| Rule | Detail |
|---|---|
| Version in path | `/api/v1`. A major version changes only on breaking changes. |
| Additive changes | New fields and endpoints are added to the current version without a bump. Clients must ignore unknown fields. |
| Deprecation | `Deprecation` and `Sunset` headers (RFC 8594) with at least 6 months notice; deprecated endpoints logged to measure remaining usage. |
| Tenant resolution | From the authenticated user (SPA) or token (integrations). Subdomain `acme.meridian.app` is used only on the login page to choose the tenant and SSO connection. |

## 2. Conventions

| Topic | Convention |
|---|---|
| Resource names | Plural, kebab-case: `/leave-requests`, `/job-grades` |
| JSON keys | `snake_case` |
| IDs | UUID strings |
| Dates | `YYYY-MM-DD`; timestamps ISO-8601 UTC `2026-11-10T08:15:00Z` |
| Money | `{ "amount": 1250000, "currency": "LKR" }` (minor units) |
| Nested resources | One level only: `/employees/{id}/documents`. Deeper nesting gets its own top-level resource. |
| State transitions | Explicit action sub-resources, not status PATCHes: `POST /leave-requests/{id}/approve` |
| Partial updates | `PATCH` with only changed fields |
| Concurrency | Responses include `ETag: "v7"`; `PATCH`/`DELETE` on aggregates require `If-Match`, else `428 Precondition Required`; mismatch → `412` |
| Idempotency | `POST` accepts `Idempotency-Key`; replays return the original response |
| Request tracing | `X-Request-Id` accepted or generated, always returned |

## 3. Response Shapes

### Single resource

```json
{
  "data": {
    "id": "0193a4c2-7f1e-7b3a-9c41-2d5e8f0a1b23",
    "type": "employee",
    "employee_number": "EMP-00421",
    "first_name": "Nimal",
    "last_name": "Perera",
    "status": "active",
    "hire_date": "2022-04-01",
    "department": { "id": "…", "name": "Platform Engineering" },
    "manager": { "id": "…", "name": "Ayesha Fernando" },
    "links": { "self": "/api/v1/employees/0193a4c2-…" },
    "meta": { "version": 7, "permissions": ["update", "terminate"] }
  }
}
```

`meta.permissions` tells the UI which actions the current user can take on *this* record, so the UI never re-implements authorisation rules.

### Collection (cursor pagination)

```json
{
  "data": [ { "...": "..." } ],
  "meta": { "per_page": 25, "next_cursor": "eyJpZCI6Ij…", "prev_cursor": null },
  "links": { "next": "/api/v1/employees?cursor=eyJpZCI6Ij…&per_page=25" }
}
```

Cursor pagination is the default: stable under concurrent inserts and fast on large tables. Offset pagination (`page`, `total`) is available only on small admin lists where a total count is useful.

### Errors — RFC 9457 Problem Details

```json
{
  "type": "https://docs.meridian.app/errors/validation",
  "title": "The given data was invalid.",
  "status": 422,
  "code": "validation_failed",
  "detail": "2 fields are invalid.",
  "instance": "/api/v1/leave-requests",
  "request_id": "5f0c1e8a-…",
  "errors": {
    "end_date": [{ "code": "after_or_equal", "message": "End date must be on or after start date." }],
    "leave_type_id": [{ "code": "exists", "message": "Selected leave type is invalid." }]
  }
}
```

Content type: `application/problem+json`. Domain exceptions map to stable `code` values the frontend can switch on:

| Status | `code` examples | When |
|---|---|---|
| 400 | `bad_request` | Malformed JSON |
| 401 | `unauthenticated`, `mfa_required` | No / incomplete session |
| 403 | `forbidden` | Authenticated, not permitted |
| 404 | `not_found` | Missing **or** not visible to this user (no existence leak) |
| 409 | `invalid_state_transition`, `duplicate_employee_number` | Business conflict |
| 412 / 428 | `version_mismatch`, `precondition_required` | Optimistic locking |
| 422 | `validation_failed`, `insufficient_leave_balance` | Input or business rule violation |
| 429 | `rate_limited` | With `Retry-After` |
| 500 | `internal_error` | Generic message only; details in logs by `request_id` |

## 4. Querying

| Feature | Syntax | Example |
|---|---|---|
| Filter | `filter[field]=value`, comma = OR | `filter[status]=active,on_leave&filter[department_id]=…` |
| Range | `filter[field][gte]=` | `filter[hire_date][gte]=2024-01-01` |
| Search | `q=` | `q=perera` (full-text on name, number, email) |
| Sort | `sort=field,-field` | `sort=last_name,-hire_date` |
| Sparse fields | `fields=a,b` | `fields=id,first_name,last_name` |
| Includes | `include=a,b` | `include=department,manager` |
| Page | `cursor=`, `per_page=` (max 100) | |

Every filterable, sortable and includable field is **whitelisted** per endpoint (a small in-house parser in `Shared\Http\Query`; no query-builder package). Unknown filters, sort fields or values → `422 validation_failed`. This avoids unindexed queries and accidental data exposure through includes.

## 5. Endpoint Catalogue (v1)

### Auth & Session — see [06-authentication.md](06-authentication.md)

✅ = implemented; the others are planned. The machine-readable contract is [`packages/api-contract/spec/openapi.yaml`](../packages/api-contract/spec/openapi.yaml), served by Swagger UI at `/docs` when `API_DOCS_ENABLED=true`.

| Method | Path | Description |
|---|---|---|
| POST | `/auth/login` ✅ | E-mail + password → JWT access token + refresh-token cookie |
| POST | `/auth/refresh` ✅ | Rotate the refresh token (cookie) → new access token |
| POST | `/auth/logout` ✅ | End the current session |
| POST | `/auth/logout-all` ✅ | End every session of the user |
| POST | `/auth/forgot-password` ✅ | Send reset e-mail (always 202) |
| POST | `/auth/reset-password` ✅ | Reset with token; signs out everywhere |
| GET | `/auth/email/verify/{id}/{hash}` ✅ | Signed e-mail link; redirects to the SPA |
| POST | `/auth/email/verification-notification` ✅ | Re-send the verification e-mail |
| POST | `/auth/mfa/verify` | Submit TOTP or recovery code |
| GET | `/auth/sso/{tenant}/redirect` | Start OIDC/SAML login |
| GET | `/auth/sso/{tenant}/callback` | IdP callback |
| GET | `/me` ✅ | Current user, roles and effective permissions (employee profile and tenant settings later) |
| POST | `/me/mfa` | Begin MFA enrolment (returns QR provisioning URI) |
| POST | `/me/mfa/confirm` | Confirm enrolment, returns recovery codes once |
| GET / DELETE | `/me/sessions`, `/me/sessions/{id}` | List and revoke active sessions |
| GET / POST / DELETE | `/me/tokens` | Personal access tokens for integrations |

### Organisation

| Method | Path | Permission |
|---|---|---|
| GET / POST | `/departments` ✅ | `organisation.view` / `organisation.manage` — full list with `meta.total` |
| GET / PATCH / DELETE | `/departments/{id}` ✅ | same; PATCH/DELETE require `If-Match`; DELETE refused while sub-departments or employees exist |
| GET | `/departments/{id}/tree` | `organisation.view` |
| POST | `/departments/{id}/move` | `organisation.manage` |
| CRUD | `/positions`, `/job-grades`, `/locations`, `/cost-centres` | `organisation.*` |

### Employees

| Method | Path | Permission |
|---|---|---|
| GET | `/employees/headcount` ✅ | `employee.view` — total and per status (dashboard) |
| GET | `/employees` ✅ | `employee.view` — `q`, `filter[status\|employment_type\|manager_id\|hire_date][gte\|lte]\|trashed]`, `sort`, cursor pagination |
| POST | `/employees` ✅ | `employee.create` (+ `employee.update.sensitive` for PII fields) |
| GET | `/employees/{id}` ✅ | `employee.view` (PII only with `employee.view.sensitive`) |
| PATCH | `/employees/{id}` ✅ | `employee.update` (+ `employee.update.sensitive` for PII fields), `If-Match` required |
| DELETE | `/employees/{id}` ✅ | `employee.delete` — soft delete, `If-Match` required; refused while the employee has direct reports |
| POST | `/employees/{id}/restore` ✅ | `employee.delete` |
| GET / POST / DELETE | `/employees/{id}/photo` ✅ | `employee.view` / `employee.update` — multipart upload, private storage, streamed through the API |
| POST | `/employees/{id}/transfer` | `employee.transfer` — creates new employment record |
| POST | `/employees/{id}/terminate` | `employee.terminate` |
| POST | `/employees/{id}/rehire` | `employee.create` |
| GET | `/employees/{id}/employment-history` | `employee.view` |
| GET | `/employees/{id}/direct-reports` | `employee.view` |
| CRUD | `/employees/{id}/emergency-contacts` | `employee.update` or self |
| GET | `/employees/{id}/documents` | `document.view` |
| POST | `/employees/{id}/documents/upload-url` | `document.upload` — returns presigned S3 POST |
| GET | `/documents/{id}/download-url` | `document.view` — short-lived presigned GET (60 s) |
| GET | `/org-chart?root={id}&depth=3` | `employee.view` |

### Compensation

| Method | Path | Permission |
|---|---|---|
| GET | `/employees/{id}/salaries` | `compensation.view` |
| POST | `/employees/{id}/salaries` | `compensation.manage` — new effective-dated record |
| GET | `/employees/{id}/allowances` | `compensation.view` |

### Leave

| Method | Path | Permission |
|---|---|---|
| CRUD | `/leave-types`, `/leave-policies` | `leave.configure` |
| POST | `/leave-policies/{id}/assignments` | `leave.configure` |
| GET | `/employees/{id}/leave-balances` | self or `leave.view` |
| POST | `/employees/{id}/leave-adjustments` | `leave.adjust` (note required, audited) |
| GET | `/leave-requests` | self / `leave.view` (scoped) |
| POST | `/leave-requests` | self or `leave.request.on_behalf` |
| GET | `/leave-requests/{id}` | |
| POST | `/leave-requests/{id}/submit` \| `/approve` \| `/reject` \| `/cancel` | workflow transitions |
| GET | `/approvals/pending` | current user's approval inbox |
| GET | `/leave-calendar?from=&to=&department_id=` | `leave.view` (team calendar) |
| CRUD | `/public-holidays` | `leave.configure` |

### Attendance

| Method | Path | Permission |
|---|---|---|
| POST | `/attendance/clock-in`, `/attendance/clock-out` | self |
| GET | `/attendance-records` | self / `attendance.view` (scoped) |
| PATCH | `/attendance-records/{id}` | `attendance.manage` (correction, audited) |
| CRUD | `/shifts` | `attendance.configure` |

### Administration

| Method | Path | Permission |
|---|---|---|
| GET | `/permissions` ✅ | `access.manage` |
| GET ✅ / POST / PATCH / DELETE | `/roles` | `access.manage` |
| GET / POST / DELETE | `/users/{id}/role-assignments` | `access.manage` |
| CRUD | `/users` (invite, disable, unlock) | `user.manage` |
| GET | `/audit-events?filter[subject_id]=…` ✅ | `audit.view` — also `filter[subject_type\|actor_id\|action]`, newest first |
| GET / PATCH | `/tenant/settings` | `tenant.manage` |
| CRUD | `/tenant/sso-connections` | `tenant.manage` |
| CRUD | `/webhooks` | `integration.manage` |

### Reporting & Exports

| Method | Path | Description |
|---|---|---|
| POST | `/exports` | Start async export (`type=headcount`, `format=csv\|xlsx`). Returns `202` + export id |
| GET | `/exports/{id}` | Status; when `completed`, includes a presigned download URL |
| GET | `/reports/headcount?group_by=department` | Small synchronous aggregates, served from read replica |

### Operations (not under `/api/v1`)

| Path | Purpose |
|---|---|
| `GET /health/live` | Process is up (ALB/ECS liveness) |
| `GET /health/ready` | DB, Redis and SQS reachable (readiness) |
| `GET /docs` | Rendered OpenAPI (non-production or authenticated) |

## 6. Rate Limiting

Implemented with Laravel's `RateLimiter` backed by Redis. Limits are returned in `RateLimit-*` headers.

| Bucket | Limit |
|---|---|
| Login / MFA / password reset | 5 / min per (email + IP), 20 / min per IP |
| Authenticated SPA | 300 / min per user |
| API tokens (integrations) | 600 / min per token, configurable per tenant |
| Exports | 5 concurrent per tenant |

AWS WAF adds an outer layer: IP rate rules, AWS managed rule sets (common, SQLi, known bad inputs).

## 7. Asynchronous Operations

Long-running work (exports, bulk imports, mass leave accrual) returns `202 Accepted` with a status resource. The SPA polls the status endpoint or receives an in-app notification on completion. Jobs carry `tenant_id`, `user_id` and `request_id` so logs and authorisation stay correct inside workers.

## 8. Webhooks (Outbound)

- Events: `employee.created`, `employee.terminated`, `leave_request.approved`, …
- Delivered from the outbox via SQS; retries with exponential backoff for 24 h, then marked failed and visible in the admin UI.
- Signed with `Meridian-Signature: t=<ts>,v1=<HMAC-SHA256>`; receivers verify timestamp (5 min tolerance) to stop replays.

## 9. Controller Shape (illustrative, not final code)

Controllers stay thin: validate → call one Action or Query → return a Resource.

```text
LeaveRequestController@approve(ApproveLeaveRequestRequest $request, string $id)
    → ApproveLeaveRequest::handle(ApproveLeaveRequestData::from($request), $request->user())
    → return LeaveRequestResource::make($leaveRequest)
```
