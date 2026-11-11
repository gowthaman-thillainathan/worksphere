# 02 — Folder Structure

## 1. Monorepo Layout

A single repository holds the backend, frontend, infrastructure and documentation. Changes that span API and UI ship in one pull request, and the OpenAPI contract sits between them.

```text
meridian/
├── apps/
│   ├── api/                      # Laravel 12 backend
│   └── web/                      # React + TypeScript SPA
├── packages/
│   └── api-contract/             # openapi.yaml + generated TS types (shared)
├── infra/
│   ├── docker/                   # Dockerfiles, nginx, php-fpm, supervisord configs
│   │   ├── api/
│   │   │   ├── Dockerfile        # multi-stage: composer → app → runtime
│   │   │   ├── nginx.conf
│   │   │   ├── php.ini
│   │   │   └── php-fpm.conf
│   │   └── web/Dockerfile        # dev server only; prod is static S3
│   └── terraform/
│       ├── modules/              # reusable: network, ecs-service, rds, redis, s3-cdn, sqs, waf, observability
│       └── envs/
│           ├── staging/
│           └── production/
├── docs/
│   ├── 01-architecture.md … 10-testing-strategy.md
│   ├── adr/                      # Architecture Decision Records
│   ├── runbooks/                 # incident playbooks (DB failover, queue backlog, key rotation)
│   └── diagrams/                 # exported diagram sources
├── scripts/                      # dev helpers: bootstrap.sh, seed-demo.sh, generate-types.sh
├── tests/
│   ├── e2e/                      # Playwright (cross-app, runs against docker-compose)
│   └── load/                     # k6 scenarios
├── .github/
│   ├── workflows/                # ci.yml, deploy-staging.yml, deploy-production.yml, security.yml
│   ├── CODEOWNERS
│   ├── pull_request_template.md
│   └── dependabot.yml
├── docker-compose.yml            # local stack: api, worker, web, postgres, redis, localstack, mailpit
├── Makefile                      # make up | test | lint | migrate | types | e2e
├── .editorconfig
├── CONTRIBUTING.md
├── SECURITY.md
└── README.md
```

## 2. Backend — `apps/api`

Laravel's default `app/` folder stays thin (bootstrapping, framework glue). Business code lives in `src/Modules`, autoloaded under the `Meridian\` namespace.

```text
apps/api/
├── app/
│   ├── Console/Kernel.php
│   ├── Exceptions/Handler.php        # maps domain exceptions → RFC 7807 problem responses
│   ├── Http/
│   │   ├── Kernel.php
│   │   └── Middleware/               # AssignRequestId, ResolveTenant, EnsureMfaVerified, SecurityHeaders
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── ModuleServiceProvider.php # discovers and boots every module
├── bootstrap/
├── config/
├── database/
│   ├── migrations/                   # framework tables only (jobs, cache, sessions)
│   └── seeders/DatabaseSeeder.php    # calls module seeders
├── routes/
│   ├── api.php                       # mounts /api/v1 and includes module route files
│   └── console.php
├── src/
│   ├── Shared/                       # shared kernel (tiny, stable)
│   │   ├── Domain/                   # AggregateRoot, DomainEvent, ValueObject base, Money, DateRange, Uuid
│   │   ├── Application/              # Action interface, Transactional, Pagination DTOs
│   │   ├── Infrastructure/
│   │   │   ├── Tenancy/              # TenantContext, TenantScope, RLS session setter
│   │   │   ├── Outbox/               # OutboxRepository, RelayOutboxCommand
│   │   │   ├── Logging/              # JsonFormatter, PiiRedactionProcessor, ContextProcessor
│   │   │   └── Persistence/          # BaseEloquentRepository, UuidPrimaryKey trait
│   │   └── Http/                     # ApiController, ProblemDetails, CursorPaginatedResource
│   └── Modules/
│       ├── Identity/
│       ├── Tenancy/
│       ├── Access/
│       ├── Organisation/
│       ├── Employee/
│       ├── Leave/
│       ├── Attendance/
│       ├── Compensation/
│       ├── Documents/
│       ├── Audit/
│       ├── Notification/
│       └── Reporting/                # read-only query layer allowed to join across modules
├── tests/
│   ├── Architecture/                 # Pest Arch rules (module boundaries, layering)
│   ├── Pest.php
│   └── TestCase.php
├── phpstan.neon                      # Larastan level max
├── pint.json                         # code style
├── infection.json5                   # mutation testing
├── rector.php
└── composer.json
```

### 2.1 Anatomy of a module — `src/Modules/Leave`

```text
Leave/
├── Contracts/                        # the ONLY namespace other modules may import
│   ├── LeaveBalanceReader.php        # interface
│   └── Data/LeaveBalanceData.php     # read-only DTO
├── Domain/
│   ├── LeaveRequest.php              # aggregate root: submit(), approve(), reject(), cancel()
│   ├── LeaveBalanceLedger.php
│   ├── ValueObjects/
│   │   ├── LeaveRequestStatus.php    # enum with allowed transitions
│   │   └── LeaveDuration.php         # working days, half days
│   ├── Services/WorkingDayCalculator.php
│   ├── Events/
│   │   ├── LeaveRequestSubmitted.php
│   │   └── LeaveRequestApproved.php
│   ├── Exceptions/InsufficientLeaveBalance.php
│   └── Repositories/LeaveRequestRepository.php   # interface
├── Application/
│   ├── Actions/
│   │   ├── SubmitLeaveRequest.php
│   │   ├── ApproveLeaveRequest.php
│   │   └── AccrueMonthlyLeave.php
│   ├── Queries/ListLeaveRequests.php
│   ├── Data/SubmitLeaveRequestData.php
│   └── Listeners/CreditBalanceOnPolicyAssigned.php
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Models/LeaveRequestModel.php
│   │   ├── EloquentLeaveRequestRepository.php
│   │   └── Mappers/LeaveRequestMapper.php
│   └── Jobs/AccrueMonthlyLeaveJob.php
├── Http/
│   ├── Controllers/LeaveRequestController.php
│   ├── Requests/SubmitLeaveRequestRequest.php
│   ├── Resources/LeaveRequestResource.php
│   └── routes.php
├── Policies/LeaveRequestPolicy.php
├── Database/
│   ├── Migrations/
│   ├── Factories/
│   └── Seeders/
├── Tests/
│   ├── Unit/                         # pure domain tests, no DB, no framework
│   ├── Feature/                      # HTTP + DB tests
│   └── Integration/                  # repositories against real PostgreSQL
└── LeaveServiceProvider.php          # binds interfaces, registers routes, migrations, listeners, policies
```

**Why tests live inside the module:** deleting or extracting a module takes its tests with it, and code owners for a module own its tests too.

## 3. Frontend — `apps/web`

The implemented structure, the API layer, authentication, state management and component design are documented in **[11 — Frontend Architecture](11-frontend-architecture.md)**.

In short:
- `app/` holds bootstrap and routes; `layouts/` the shell; `routes/` the guards and paths.
- `features/<area>/` is one folder per business area, each with its pages, components, hooks and schemas.
- `components/` is the design system; `hooks/` and `shared/` hold generic hooks and helpers.
- `services/` holds the API client and one service per resource; `services/api/generated/` is the Orval output, never edited by hand.
- `types/` holds the permission keys and re-exported API types.

Only the service layer may import the generated client; ESLint enforces this.

## 4. Naming Conventions

| Item | Convention | Example |
|---|---|---|
| Actions | Verb + noun, one public `handle()` / `__invoke()` | `ApproveLeaveRequest` |
| Queries | `List…`, `Get…`, `Search…` | `ListLeaveRequests` |
| Events | Past tense | `EmployeeTerminated` |
| DTOs | `…Data` | `SubmitLeaveRequestData` |
| Tables | plural snake_case, module prefix only where ambiguous | `leave_requests` |
| Permissions | `resource.action[.qualifier]` | `employee.update.compensation` |
| React components | PascalCase file per component | `EmployeeTable.tsx` |
| React hooks | `use` + noun/verb | `useApproveLeaveRequest` |
| Branches | `type/ticket-short-desc` | `feat/MER-142-leave-approval` |
| Commits | Conventional Commits | `feat(leave): add half-day requests` |
