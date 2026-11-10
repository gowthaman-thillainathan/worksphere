# 0004 — Full Domain Model Only Where Business Rules Justify It

- Status: Accepted
- Date: 2026-11-10

## Context

A strict hexagonal design (framework-free entities, repositories, mappers) costs more code per feature. Some modules (Leave, Employee lifecycle, Compensation) have rich rules: state machines, balance ledgers, effective dating. Others (Locations, Cost Centres, Shifts) are simple reference data.

## Decision

- **Rich modules** use the full layering: Domain entities and value objects without Laravel dependencies, repository interfaces, Eloquent implementations and mappers.
- **Simple CRUD modules** use Eloquent models directly inside Application Actions, still behind Actions and Policies, still within module boundaries.
- A module moves from simple to rich when its rules grow; this is a refactor inside the module and does not affect others.

## Consequences

### Positive
- Effort goes where complexity is; reference-data features stay quick to build.
- Domain rules that matter most are unit-testable without a database.

### Negative
- Two styles exist in one codebase; the module README states which style it uses.
- Reviewers must judge when a simple module has become complex enough to change style.

## Alternatives considered
- **Full DDD everywhere:** consistent but heavy; much boilerplate for little benefit.
- **Active Record everywhere:** fast at first, but business rules leak into controllers, models and observers and become hard to test.
