# 0002 — Shared-Database Multi-Tenancy with PostgreSQL RLS

- Status: Accepted
- Date: 2026-11-10

## Context

Meridian serves many organisations. HR data is highly sensitive; a cross-tenant leak would be a critical incident. Hundreds of small-to-medium tenants are expected, with a few large ones.

## Decision

Use one database and schema with a `tenant_id` column on every tenant-owned table. Enforce isolation in two independent layers:

1. **Application:** a global Eloquent scope and `TenantContext` set by middleware/job middleware.
2. **Database:** PostgreSQL Row-Level Security policies keyed on `current_setting('app.tenant_id')`. The application DB role has no `BYPASSRLS`.

## Consequences

### Positive
- A bug in the application layer (a forgotten scope, a raw query) cannot leak another tenant's data.
- Single schema → one migration run, simple operations, efficient resource use.
- Tenant context missing → queries fail closed.

### Negative
- Every connection must set `app.tenant_id` reliably, including queued jobs and connection pooling (`SET LOCAL` per transaction under RDS Proxy).
- RLS adds a small planner overhead; composite indexes must lead with `tenant_id`.
- Noisy-neighbour risk; mitigated by per-tenant rate limits and, if needed, moving a large tenant to its own database.

## Alternatives considered
- **Database per tenant:** strongest isolation, but migrations and connections become hard to manage at hundreds of tenants.
- **Schema per tenant:** similar migration fan-out, catalogue bloat in PostgreSQL.
- **`tenant_id` without RLS:** relies on application code alone — rejected for the sensitivity of HR data.
