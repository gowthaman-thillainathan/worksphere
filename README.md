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
| Backend | PHP 8.3, Laravel 12, Sanctum, queue workers on Amazon SQS |
| Frontend | React 18, TypeScript (strict), Vite, TanStack Query, React Router, React Hook Form + Zod, Tailwind CSS, shadcn/ui |
| Data | PostgreSQL 16 (RLS, partitioning), Redis 7 (cache, locks, rate limits) |
| Infrastructure | Docker, AWS ECS Fargate, RDS, ElastiCache, S3, CloudFront, SQS, SES, Secrets Manager, WAF |
| IaC / CI/CD | Terraform, GitHub Actions (OIDC to AWS), CodeDeploy blue/green |
| Observability | Monolog JSON → CloudWatch Logs, OpenTelemetry → AWS X-Ray, CloudWatch metrics & alarms, Sentry |
| Quality | Pest, Larastan (level max), Pest Arch, Infection, Vitest, Testing Library, MSW, Playwright, k6 |

## Documentation

| # | Document | Summary |
|---|---|---|
| 01 | [Overall Architecture](docs/01-architecture.md) | System context, containers, module map, request lifecycle |
| 02 | [Folder Structure](docs/02-folder-structure.md) | Monorepo layout, backend module anatomy, frontend feature slices |
| 03 | [Database Design](docs/03-database-design.md) | Schema, conventions, indexing, partitioning, RLS, encryption |
| 04 | [ER Diagram](docs/04-er-diagram.md) | Entity-relationship diagrams per bounded context |
| 05 | [API Structure](docs/05-api-structure.md) | REST conventions, versioning, errors, pagination, endpoint catalogue |
| 06 | [Authentication Flow](docs/06-authentication.md) | SPA sessions, MFA, SSO, API tokens, password and session policy |
| 07 | [RBAC Design](docs/07-rbac.md) | Roles, permissions, scopes, policies, field-level access |
| 08 | [Deployment Architecture](docs/08-deployment.md) | AWS topology, environments, CI/CD, zero-downtime migrations, DR |
| 09 | [Logging & Observability](docs/09-logging-observability.md) | Structured logs, correlation, tracing, metrics, audit trail |
| 10 | [Testing Strategy](docs/10-testing-strategy.md) | Test pyramid, tooling, quality gates, test data |
| — | [Architecture Decision Records](docs/adr/README.md) | Key decisions and their trade-offs |

## Roadmap

| Phase | Scope |
|---|---|
| **1 — Foundation** | Monorepo, Docker dev environment, CI pipeline, Identity (auth, MFA), Tenancy, RBAC, Audit |
| **2 — Core HR** | Organisation (departments, positions, locations), Employee profiles, Documents |
| **3 — Time** | Leave policies and requests with approval workflow, Attendance |
| **4 — Compensation** | Salary history, bands, field-level protection |
| **5 — Production** | Terraform for AWS, blue/green deploys, dashboards, load tests, runbooks |
| **6 — Extensions** | SSO (OIDC/SAML), reporting exports, webhooks, performance reviews |

## Licence

MIT
