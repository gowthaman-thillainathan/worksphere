# Architecture Decision Records

ADRs record significant decisions, the context they were made in and the trade-offs accepted. Format: [Michael Nygard's template](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions). ADRs are immutable once accepted; a changed decision gets a new ADR that supersedes the old one.

| # | Decision | Status |
|---|---|---|
| [0001](0001-modular-monolith.md) | Modular monolith instead of microservices | Accepted |
| [0002](0002-multi-tenancy-rls.md) | Shared-database multi-tenancy with PostgreSQL Row-Level Security | Accepted |
| [0003](0003-spa-session-auth.md) | Cookie sessions (Sanctum) for the SPA instead of JWT | Superseded by 0006 |
| [0004](0004-pragmatic-ddd.md) | Full domain model only where business rules justify it | Accepted |
| [0005](0005-sqs-queues.md) | Amazon SQS for queues in AWS environments | Accepted |
| [0006](0006-jwt-access-and-rotating-refresh-tokens.md) | JWT access tokens with rotating refresh tokens | Accepted |
| [0007](0007-employee-sign-in-accounts.md) | Employee sign-in accounts by invitation, owned by Identity | Accepted |
| [0008](0008-denormalised-department-name.md) | Copy of the department name on employees for sorting | Accepted |

## Template

```markdown
# NNNN — Title

- Status: Proposed | Accepted | Superseded by NNNN
- Date: YYYY-MM-DD

## Context
## Decision
## Consequences
### Positive
### Negative
## Alternatives considered
```
