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

Feature-sliced structure: code is grouped by business feature, not by technical type.

```text
apps/web/
├── public/
├── src/
│   ├── app/                          # application shell
│   │   ├── main.tsx
│   │   ├── App.tsx
│   │   ├── router.tsx                # route tree, lazy-loaded feature routes
│   │   ├── providers/                # QueryClientProvider, AuthProvider, ThemeProvider, I18nProvider
│   │   └── layouts/                  # AppLayout, AuthLayout
│   ├── features/
│   │   ├── auth/
│   │   │   ├── api/                  # useLogin, useMfaVerify, useMe
│   │   │   ├── components/           # LoginForm, MfaChallenge
│   │   │   ├── routes/               # LoginPage, ForgotPasswordPage
│   │   │   └── index.ts              # public surface of the feature
│   │   ├── employees/
│   │   │   ├── api/                  # useEmployees, useEmployee, useUpdateEmployee (TanStack Query)
│   │   │   ├── components/           # EmployeeTable, EmployeeForm, OrgChart
│   │   │   ├── routes/               # EmployeeListPage, EmployeeDetailPage
│   │   │   ├── schemas/              # Zod schemas
│   │   │   └── index.ts
│   │   ├── leave/
│   │   ├── attendance/
│   │   ├── organisation/
│   │   ├── compensation/
│   │   ├── documents/
│   │   └── admin/                    # roles, permissions, tenant settings, audit viewer
│   ├── shared/
│   │   ├── ui/                       # design-system components (shadcn/ui based)
│   │   ├── components/               # DataTable, PageHeader, EmptyState, ErrorBoundary
│   │   ├── auth/                     # <Can>, usePermission, RequireAuth
│   │   ├── hooks/                    # useDebounce, usePagination
│   │   ├── lib/
│   │   │   ├── http.ts               # fetch wrapper: CSRF, request id, problem+json parsing
│   │   │   ├── query-client.ts
│   │   │   └── format.ts             # dates, money (tenant locale)
│   │   ├── api-types/                # generated from packages/api-contract
│   │   └── i18n/
│   ├── test/                         # test utils, MSW handlers, render helpers
│   └── styles/
├── e2e/ → ../../tests/e2e            # symlinked for IDE convenience
├── eslint.config.js                  # incl. eslint-plugin-boundaries (features can't import each other's internals)
├── vite.config.ts
├── vitest.config.ts
├── tsconfig.json                     # strict: true, noUncheckedIndexedAccess
└── package.json
```

### Frontend import rules (enforced by `eslint-plugin-boundaries`)

| From | May import |
|---|---|
| `app/` | `features/*/index.ts`, `shared/*` |
| `features/x` | its own files, `shared/*`, other features **only via** their `index.ts` |
| `shared/` | `shared/*` only — never `features` |

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
