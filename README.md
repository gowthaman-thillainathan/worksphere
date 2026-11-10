# Meridian — Enterprise Employee Management Platform

> A multi-tenant, audit-ready Employee Management System built as a **modular monolith** on Laravel, React, PostgreSQL, Docker and AWS.

Meridian manages the full employee lifecycle — onboarding, organisation structure, leave, attendance, compensation, documents and offboarding — with fine-grained, scope-aware access control and a tamper-evident audit trail.

This repository is a reference implementation. It shows how a production system is designed, documented, tested and run, not only how features are written.

---

## Design Principles

| Principle | What it means in this codebase |
|---|---|
| **Modular monolith first** | One deployable, strict module boundaries enforced by architecture tests. Modules can be split into services later without rewriting them. |
| **Domain over framework** | Business rules live in plain PHP domain classes. Laravel is the delivery mechanism, not the architecture. |
| **Secure by default** | Deny-by-default authorisation, PostgreSQL Row-Level Security as a second tenant barrier, PII encrypted at rest, MFA. |
| **Everything is auditable** | Every state change on sensitive data produces an immutable, hash-chained audit event. |
| **Operable** | Structured logs, distributed traces, health checks, runbooks, zero-downtime deploys. |
| **Contract-driven** | OpenAPI is the source of truth; frontend types and contract tests are generated from it. |
| **Decisions are written down** | Architecture Decision Records (ADRs) explain *why*, not just *what*. |

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Laravel 12, JWT (`firebase/php-jwt`) with rotating refresh tokens, queue workers on Amazon SQS |
| Frontend | React 19, TypeScript (strict), Vite, TanStack Query, React Router, React Hook Form + Zod, Tailwind CSS, shadcn/ui |
| Data | PostgreSQL 16 (RLS, partitioning), Redis 7 (cache, locks, rate limits) |
| Infrastructure | Docker, AWS ECS Fargate, RDS, ElastiCache, S3, CloudFront, SQS, SES, Secrets Manager, WAF |
| IaC / CI/CD | Terraform, GitHub Actions (OIDC to AWS), CodeDeploy blue/green |
| Observability | Monolog JSON → CloudWatch Logs, OpenTelemetry → AWS X-Ray, CloudWatch metrics & alarms, Sentry |
| Quality | Pest, Pest Arch, Pint, Larastan (level max), Infection, Vitest, Testing Library, MSW, Playwright, k6 |

## Documentation

| # | Document | Summary |
|---|---|---|
| 01 | [Overall Architecture](docs/01-architecture.md) | System context, containers, module map, request lifecycle |
| 02 | [Folder Structure](docs/02-folder-structure.md) | Monorepo layout, backend module anatomy, frontend feature slices |
| 03 | [Database Design](docs/03-database-design.md) | Schema, conventions, indexing, partitioning, RLS, encryption |
| 04 | [ER Diagram](docs/04-er-diagram.md) | Entity-relationship diagrams per bounded context |
| 05 | [API Structure](docs/05-api-structure.md) | REST conventions, versioning, errors, pagination, endpoint catalogue |
| 06 | [Authentication Flow](docs/06-authentication.md) | JWT access tokens, rotating refresh tokens, password reset, e-mail verification, MFA/SSO plan |
| 07 | [RBAC Design](docs/07-rbac.md) | Roles, permissions, scopes, policies, field-level access |
| 08 | [Deployment Architecture](docs/08-deployment.md) | AWS topology, environments, CI/CD, zero-downtime migrations, DR |
| 09 | [Logging & Observability](docs/09-logging-observability.md) | Structured logs, correlation, tracing, metrics, audit trail |
| 10 | [Testing Strategy](docs/10-testing-strategy.md) | Test pyramid, tooling, quality gates, test data |
| — | [Architecture Decision Records](docs/adr/README.md) | Key decisions and their trade-offs |

## Getting Started (API)

**Requirements:** PHP 8.3+ (`pdo_pgsql`, `intl`, `sodium`), Composer 2, PostgreSQL 16.

```bash
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
php -r "echo 'base64:'.base64_encode(random_bytes(64)).PHP_EOL;"   # paste into JWT_SECRET in .env
php artisan migrate
php artisan access:sync-permissions
php artisan identity:create-user admin@meridian.test --name="Ada Admin" --role=tenant_admin
php artisan serve
```

- Swagger UI: <http://localhost:8000/docs> (when `API_DOCS_ENABLED=true`)
- OpenAPI contract: [`packages/api-contract/spec/openapi.yaml`](packages/api-contract/spec/openapi.yaml)
- Verification e-mails are written to the log locally (`MAIL_MAILER=log`).

**Tests** run against a real PostgreSQL database (`meridian_test`; credentials in `apps/api/phpunit.xml`):

```bash
composer test            # Pest, in parallel
./vendor/bin/pint --test # code style
```

## Authentication at a Glance

| Endpoint | Purpose |
|---|---|
| `POST /api/v1/auth/login` | E-mail + password → 15-min JWT access token; refresh token in an `HttpOnly` cookie |
| `POST /api/v1/auth/refresh` | Single-use refresh-token rotation, with reuse detection that revokes the session |
| `POST /api/v1/auth/logout` · `logout-all` | End this session or every session (token-version bump) |
| `POST /api/v1/auth/forgot-password` · `reset-password` | Enumeration-safe password reset that signs out everywhere |
| `GET /api/v1/auth/email/verify/{id}/{hash}` | Signed, expiring verification link; resend endpoint available |
| `GET /api/v1/me` | Current user with roles and effective permissions |
| `GET /api/v1/permissions` · `roles` | RBAC catalogue (requires `access.manage`) |

Errors follow RFC 9457 (`application/problem+json`) with stable `code`s and a `request_id`. Design: [docs/06](docs/06-authentication.md), [ADR-0006](docs/adr/0006-jwt-access-and-rotating-refresh-tokens.md).

## Roadmap

| Phase | Scope |
|---|---|
| **1 — Foundation** | Monorepo, Docker dev environment, CI pipeline, Identity (✅ JWT auth, password reset, e-mail verification; MFA next), Tenancy, RBAC (✅ roles & permissions; scopes next), Audit |
| **2 — Core HR** | Organisation (departments, positions, locations), Employee profiles, Documents |
| **3 — Time** | Leave policies and requests with approval workflow, Attendance |
| **4 — Compensation** | Salary history, bands, field-level protection |
| **5 — Production** | Terraform for AWS, blue/green deploys, dashboards, load tests, runbooks |
| **6 — Extensions** | SSO (OIDC/SAML), reporting exports, webhooks, performance reviews |

## Licence

MIT
