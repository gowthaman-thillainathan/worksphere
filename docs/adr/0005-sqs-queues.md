# 0005 — Amazon SQS for Queues in AWS Environments

- Status: Accepted
- Date: 2026-11-10

## Context

Background work includes notifications, exports, webhooks, leave accrual and outbox relay. Losing jobs (for example a webhook for a termination) is unacceptable. Redis is already used for cache and sessions.

## Decision

Use Amazon SQS (standard queues), one queue per workload (`default`, `notifications`, `exports`, `webhooks`), each with a dead-letter queue after 5 receives. Workers run as a separate ECS service and autoscale on queue depth and oldest-message age. Local development uses LocalStack SQS so the driver is the same everywhere.

## Consequences

### Positive
- Durable, managed, no capacity planning; queue durability is independent of the cache.
- DLQs make failures visible and replayable.
- Native CloudWatch metrics drive autoscaling.

### Negative
- At-least-once delivery → every job must be idempotent (enforced by convention and tests).
- No Laravel Horizon dashboard; visibility comes from CloudWatch dashboards and the failed-jobs table.
- Max message size 256 KB → jobs carry IDs, never large payloads.

## Alternatives considered
- **Redis + Horizon:** excellent developer experience, but durability depends on Redis persistence settings and mixes critical jobs with cache memory.
- **SQS FIFO:** ordering not needed for these workloads; lower throughput and higher cost.
