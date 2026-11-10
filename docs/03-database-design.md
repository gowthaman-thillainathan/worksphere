# 03 — Database Design

PostgreSQL 16 on Amazon RDS. One database, one schema (`public`), shared by all tenants and isolated by `tenant_id` + Row-Level Security.

## 1. Conventions

| Topic | Rule | Reason |
|---|---|---|
| Primary keys | `uuid` (UUIDv7, generated in app) | Not guessable in URLs, time-ordered so B-tree inserts stay efficient, safe to generate before insert |
| Tenant key | `tenant_id uuid not null` on every tenant-owned table, first column in composite indexes | Isolation + index locality |
| Timestamps | `created_at`, `updated_at` as `timestamptz` (UTC) | No time zone ambiguity |
| Soft deletes | `deleted_at timestamptz` only where business requires restore (employees, documents) | Avoid soft-delete everywhere; it complicates unique constraints |
| Unique + soft delete | Partial unique indexes: `UNIQUE (tenant_id, email) WHERE deleted_at IS NULL` | |
| Optimistic locking | `version integer not null default 1` on aggregates | Supports `If-Match` / ETag |
| Enums | `text` + `CHECK` constraint (not native PG enums) | Native enums are hard to change in zero-downtime migrations |
| Money | `bigint` minor units + `char(3)` currency | No floating-point errors |
| Dates | `date` for business dates (hire date, leave days), `timestamptz` for events | |
| Foreign keys | Always declared; `ON DELETE RESTRICT` by default | Integrity over convenience |
| Naming | `snake_case`, plural tables, `fk_<table>_<column>`, `idx_<table>_<cols>`, `uq_<table>_<cols>` | |
| Audit columns | `created_by`, `updated_by` (user uuid, nullable for system) | |

## 2. Schema by Bounded Context

> Column types abbreviated: `uuid`, `text`, `ts` = `timestamptz`, `bool`, `int`, `bigint`, `date`, `jsonb`.
> Every tenant-owned table also has `tenant_id`, `created_at`, `updated_at` unless stated.

### 2.1 Tenancy

**tenants**

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| name | text | |
| slug | text UNIQUE | used in subdomain `acme.meridian.app` |
| status | text | `trial`, `active`, `suspended` |
| timezone | text | IANA, e.g. `Asia/Colombo` |
| locale | text | `en-GB` |
| currency | char(3) | default currency |
| settings | jsonb | password policy, MFA enforcement, working week |
| created_at, updated_at | ts | |

**tenant_features** — `tenant_id`, `feature` (text), `enabled` (bool). PK (`tenant_id`, `feature`).

### 2.2 Identity

**users**

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| tenant_id | uuid FK | |
| email | citext | `UNIQUE (tenant_id, email)` |
| password | text NULL | argon2id; null for SSO-only users |
| name | text | |
| status | text | `invited`, `active`, `locked`, `disabled` |
| mfa_secret | text NULL | encrypted (app-level) |
| mfa_enabled_at | ts NULL | |
| last_login_at | ts NULL | |
| password_changed_at | ts | password expiry policy |
| failed_login_count | int | lockout |
| locked_until | ts NULL | |

**mfa_recovery_codes** — `id`, `user_id`, `code_hash`, `used_at`.

**personal_access_tokens** — Sanctum standard + `tenant_id`, `abilities jsonb`, `expires_at`, `last_used_ip`.

**sessions** — stored in Redis (not PostgreSQL); listed here for completeness.

**login_attempts** — `id`, `tenant_id`, `email`, `ip`, `user_agent`, `succeeded`, `failure_reason`, `created_at`. Partitioned monthly; retained 90 days.

**sso_connections** — `id`, `tenant_id`, `protocol` (`oidc`/`saml`), `issuer`, `client_id`, `client_secret` (encrypted), `metadata jsonb`, `enforced bool`.

### 2.3 Access (RBAC) — detailed in [07-rbac.md](07-rbac.md)

**permissions** (global, seeded) — `id`, `key` UNIQUE (`employee.view`), `module`, `description`, `is_sensitive`.

**roles** — `id`, `tenant_id` NULL (null = system template), `key`, `name`, `description`, `is_system bool`. `UNIQUE (tenant_id, key)`.

**role_permissions** — `role_id`, `permission_id`. PK both.

**role_assignments** — `id`, `tenant_id`, `user_id`, `role_id`, `scope_type` (`self`, `direct_reports`, `department`, `location`, `tenant`), `scope_id` NULL, `granted_by`, `expires_at` NULL. `UNIQUE (user_id, role_id, scope_type, scope_id)`.

### 2.4 Organisation

**departments**

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| tenant_id | uuid | |
| parent_id | uuid FK NULL | self-reference |
| path | ltree | materialised path for fast subtree queries (`root.eng.platform`) |
| code | text | `UNIQUE (tenant_id, code)` |
| name | text | |
| head_employee_id | uuid FK NULL | |
| cost_centre_id | uuid FK NULL | |

GiST index on `path` → "all employees in Engineering and below" is one indexed query. This is what makes department-scoped RBAC cheap.

**locations** — `id`, `tenant_id`, `name`, `country_code`, `timezone`, `address jsonb`.

**job_grades** — `id`, `tenant_id`, `code`, `name`, `rank int`, `min_salary`, `max_salary`, `currency`.

**positions** — `id`, `tenant_id`, `department_id`, `job_grade_id`, `title`, `headcount int`, `status`.

**cost_centres** — `id`, `tenant_id`, `code`, `name`.

### 2.5 Employee

**employees**

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| tenant_id | uuid | |
| user_id | uuid FK NULL UNIQUE | login account (optional: not every employee logs in) |
| employee_number | text | `UNIQUE (tenant_id, employee_number)`, generated by tenant sequence |
| first_name, last_name, preferred_name | text | |
| work_email | citext | |
| personal_email | text | encrypted |
| phone | text | encrypted |
| date_of_birth | text | encrypted (stored as ciphertext) |
| national_id | text | encrypted; `national_id_hash` for exact-match lookup |
| gender | text NULL | optional, self-declared |
| status | text | `pre_boarding`, `active`, `on_leave`, `notice`, `terminated` |
| hire_date | date | |
| termination_date | date NULL | |
| manager_id | uuid FK NULL | self-reference → reporting line |
| department_id | uuid FK | denormalised from current employment for fast filtering |
| location_id | uuid FK | |
| version | int | optimistic lock |
| deleted_at | ts NULL | |
| search_vector | tsvector | generated column; GIN index for name/number search |

**employments** (history of job assignments — effective-dated)

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| employee_id | uuid FK | |
| position_id | uuid FK | |
| department_id | uuid FK | |
| employment_type | text | `full_time`, `part_time`, `contract`, `intern` |
| fte | numeric(3,2) | 1.00 = full time |
| effective_from | date | |
| effective_to | date NULL | |
| change_reason | text | `hire`, `promotion`, `transfer`, `restructure` |

Exclusion constraint prevents overlapping periods:
`EXCLUDE USING gist (employee_id WITH =, daterange(effective_from, effective_to, '[)') WITH &&)`.

**emergency_contacts** — `id`, `employee_id`, `name`, `relationship`, `phone` (encrypted), `is_primary`.

**employee_addresses** — `id`, `employee_id`, `type`, `address` (encrypted jsonb), `effective_from`.

### 2.6 Compensation (sensitive)

**salaries** — `id`, `tenant_id`, `employee_id`, `amount_minor bigint`, `currency`, `frequency` (`monthly`/`annual`/`hourly`), `effective_from`, `effective_to`, `reason`, `approved_by`. Same exclusion constraint as employments.

**allowances** — `id`, `tenant_id`, `employee_id`, `type`, `amount_minor`, `currency`, `recurring bool`, `effective_from`, `effective_to`.

Access requires the `compensation.*` permissions; queries never join these tables into general employee listings.

### 2.7 Leave

**leave_types** — `id`, `tenant_id`, `code` (`ANNUAL`, `SICK`), `name`, `is_paid`, `requires_attachment`, `color`.

**leave_policies** — `id`, `tenant_id`, `leave_type_id`, `name`, `accrual_method` (`upfront`, `monthly`), `annual_entitlement numeric(5,2)`, `carry_over_max`, `allow_negative bool`, `rules jsonb`.

**leave_policy_assignments** — `id`, `tenant_id`, `policy_id`, `employee_id`, `effective_from`, `effective_to`.

**leave_balance_ledger** — append-only, the source of truth for balances

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| tenant_id, employee_id, leave_type_id | uuid | |
| entry_type | text | `accrual`, `usage`, `adjustment`, `carry_over`, `expiry`, `reversal` |
| amount | numeric(6,2) | + credit / − debit, in days |
| period_year | int | |
| leave_request_id | uuid NULL | |
| note | text | required for manual adjustments |
| created_by | uuid | |
| created_at | ts | no `updated_at`: rows are never updated |

Current balance = `SUM(amount)`; a materialised `leave_balances` table (`employee_id`, `leave_type_id`, `period_year`, `balance`) is maintained in the same transaction for fast reads. A nightly job reconciles the two and alerts on any drift.

**leave_requests** — `id`, `tenant_id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `start_half` / `end_half` (`am`/`pm`/null), `days numeric(5,2)`, `reason`, `status` (`draft`, `pending`, `approved`, `rejected`, `cancelled`), `decided_by`, `decided_at`, `decision_note`, `version`.

**leave_approvals** — multi-step workflow: `id`, `leave_request_id`, `step int`, `approver_id`, `status`, `acted_at`, `comment`.

**public_holidays** — `id`, `tenant_id`, `location_id` NULL, `date`, `name`.

### 2.8 Attendance

**attendance_records** — `id`, `tenant_id`, `employee_id`, `work_date date`, `clock_in ts`, `clock_out ts`, `source` (`web`, `mobile`, `device`), `geo jsonb NULL`, `status`. **Partitioned by range on `work_date` (monthly)**; this is the highest-volume table.

**shifts** — `id`, `tenant_id`, `name`, `start_time`, `end_time`, `break_minutes`.

### 2.9 Documents

**documents** — `id`, `tenant_id`, `employee_id`, `category` (`contract`, `id_proof`, `certificate`), `title`, `s3_key`, `mime_type`, `size_bytes`, `checksum_sha256`, `version int`, `expires_on date NULL`, `uploaded_by`, `deleted_at`.

Files are never stored in PostgreSQL. Uploads go directly to S3 via presigned URLs; the API registers metadata after an S3 event confirms the upload and a malware scan (GuardDuty Malware Protection for S3) passes.

### 2.10 Audit & Platform

**audit_events** — append-only, hash-chained

| Column | Type | Notes |
|---|---|---|
| id | bigint identity | sequential per partition for chain ordering |
| tenant_id | uuid | |
| occurred_at | ts | |
| actor_type | text | `user`, `system`, `api_token` |
| actor_id | uuid NULL | |
| impersonator_id | uuid NULL | set during support impersonation |
| action | text | `employee.updated`, `leave_request.approved` |
| subject_type, subject_id | text, uuid | |
| changes | jsonb | `{field: [old, new]}` with sensitive values masked |
| ip, user_agent | text | |
| request_id | uuid | joins audit to logs and traces |
| prev_hash, hash | bytea | SHA-256 of (prev_hash ‖ canonical row) |

Partitioned monthly. The app's database role has only `INSERT` and `SELECT` on this table; `UPDATE`/`DELETE` are revoked. Old partitions are exported to S3 (Object Lock, compliance mode) before detaching.

**outbox_messages** — `id`, `tenant_id`, `event_type`, `payload jsonb`, `occurred_at`, `published_at NULL`, `attempts`. Index on `(published_at) WHERE published_at IS NULL`.

**notifications** — Laravel standard table + `tenant_id`.

**idempotency_keys** — kept in Redis (24 h TTL), not PostgreSQL.

## 3. Multi-Tenant Isolation with Row-Level Security

```sql
ALTER TABLE employees ENABLE ROW LEVEL SECURITY;
ALTER TABLE employees FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON employees
  USING (tenant_id = current_setting('app.tenant_id')::uuid)
  WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);
```

- The `ResolveTenant` middleware runs `SET app.tenant_id = '<uuid>'` on the connection for each request and each queued job (jobs carry the tenant id in their payload). Under PgBouncer transaction pooling this becomes `SET LOCAL` inside each transaction.
- If the setting is missing, `current_setting` raises an error → the query fails closed rather than leaking data.
- The application connects as `meridian_app` (no `BYPASSRLS`). Migrations run as `meridian_migrator`. Cross-tenant platform jobs use a separate `meridian_platform` role with explicit, audited usage.
- A CI test creates two tenants and tries every repository method with the wrong tenant context; all must return nothing.

## 4. Indexing Strategy

| Query pattern | Index |
|---|---|
| Employee list filtered by department, status | `(tenant_id, department_id, status)` |
| Department subtree | GiST on `departments.path` |
| Name / number search | GIN on `employees.search_vector`; `pg_trgm` GIN for fuzzy name match |
| Direct reports | `(tenant_id, manager_id)` |
| Pending approvals for a manager | `leave_approvals (approver_id, status) WHERE status = 'pending'` |
| Leave calendar | `leave_requests (tenant_id, start_date, end_date)` + GiST on `daterange` for overlap checks |
| Cursor pagination | `(tenant_id, created_at, id)` |
| Outbox relay | partial index `WHERE published_at IS NULL` |

All indexes in migrations are created with `CREATE INDEX CONCURRENTLY` (migration declares `withinTransaction = false`).

## 5. Encryption & PII

| Data | Protection |
|---|---|
| Whole database, snapshots, backups | RDS encryption at rest with a customer-managed KMS key |
| In transit | TLS enforced (`rds.force_ssl = 1`) |
| PII fields (national ID, DOB, personal phone/email, addresses) | Application-level encryption (Laravel encrypted casts, AES-256-GCM) with keys from Secrets Manager. A DB dump alone reveals nothing |
| Searchable encrypted fields | Separate blind index column: HMAC-SHA256(value, pepper) for exact match |
| Passwords | argon2id |
| Logs | PII redaction processor — see [09](09-logging-observability.md) |

**Key rotation:** Laravel `APP_PREVIOUS_KEYS` supports decrypt-with-old / encrypt-with-new; a background job re-encrypts rows in batches.

## 6. Migrations — Zero-Downtime Rules

Deploys run new code against the old schema for a short time, so every migration must be backward compatible (**expand → migrate → contract**):

1. **Expand:** add nullable columns / new tables / indexes concurrently. Old code still works.
2. **Deploy code** that writes to both old and new structures.
3. **Backfill** in batched jobs (never one huge `UPDATE`).
4. **Switch reads** to the new structure.
5. **Contract** in a later release: drop old columns.

Forbidden in a single release: renaming a column, adding `NOT NULL` without default to a large table, changing a column type in place. A CI check (`squawk` linter on generated SQL) flags dangerous statements.

## 7. Data Lifecycle

| Data | Retention | Mechanism |
|---|---|---|
| Audit events | 7 years | Monthly partitions → S3 Object Lock after 13 months |
| Login attempts | 90 days | Partition drop |
| Attendance | 3 years online | Partition detach → S3 Parquet |
| Terminated employee PII | Tenant-configurable (default 6 years) then anonymised | Scheduled `AnonymiseEmployee` action; keeps statistics, removes identity |
| Backups | 35 days PITR + monthly snapshots for 1 year | RDS automated + AWS Backup |
