# 0001 — Modular Monolith Instead of Microservices

- Status: Accepted
- Date: 2026-11-10

## Context

Meridian covers about ten bounded contexts. The expected team is 1–6 engineers. Many workflows (approve leave → debit balance → audit → notify) need strong consistency across contexts. Expected load (≈200 req/s peak) is well within what one horizontally scaled application handles.

## Decision

Build a single Laravel application split into modules under `src/Modules`, one per bounded context. Modules communicate only through their `Contracts` namespace and domain events. Boundaries are enforced by Pest Arch tests in CI. Each module owns its tables.

## Consequences

### Positive
- One deployment, one database transaction for cross-context workflows, simple local setup.
- No network failure modes, service discovery or distributed tracing needed between modules.
- Boundaries still exist and are tested, so a module can be extracted later.

### Negative
- All modules scale together; a hot module (e.g. Attendance) cannot scale on its own.
- Requires discipline: architecture tests must be maintained, and shortcuts are always tempting.
- One shared runtime: a memory leak or bad dependency affects everything.

## Alternatives considered
- **Microservices:** rejected — operational overhead and eventual consistency outweigh benefits at this scale.
- **Traditional Laravel structure (`app/Models`, `app/Http/Controllers`):** rejected — no boundaries; becomes a ball of mud as the domain grows.
