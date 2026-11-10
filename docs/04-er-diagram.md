# 04 — ER Diagram

Diagrams are written in Mermaid so GitHub renders them and they stay version-controlled with the schema. Full column definitions are in [03-database-design.md](03-database-design.md); diagrams show keys and the most important attributes only.

`tenant_id` exists on every tenant-owned table. It is omitted from relationships below to keep diagrams readable.

## 1. High-Level Context Map

```mermaid
flowchart LR
    Tenancy --> Identity
    Tenancy --> Organisation
    Identity --> Access
    Organisation --> Employee
    Identity -. user_id .-> Employee
    Employee --> Leave
    Employee --> Attendance
    Employee --> Compensation
    Employee --> Documents
    Access -. scope .-> Organisation
    Audit((Audit)) -. observes all .- Employee
```

## 2. Identity, Tenancy & Access

```mermaid
erDiagram
    TENANTS ||--o{ USERS : has
    TENANTS ||--o{ TENANT_FEATURES : enables
    TENANTS ||--o{ ROLES : defines
    TENANTS ||--o{ SSO_CONNECTIONS : configures
    USERS ||--o{ ROLE_ASSIGNMENTS : receives
    ROLES ||--o{ ROLE_ASSIGNMENTS : "granted via"
    ROLES ||--o{ ROLE_PERMISSIONS : contains
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : "included in"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : owns
    USERS ||--o{ MFA_RECOVERY_CODES : has

    TENANTS {
        uuid id PK
        text slug UK
        text status
        text timezone
        jsonb settings
    }
    USERS {
        uuid id PK
        uuid tenant_id FK
        citext email "UK per tenant"
        text password "argon2id, nullable"
        text status
        ts mfa_enabled_at
    }
    ROLES {
        uuid id PK
        uuid tenant_id FK "null = system template"
        text key
        bool is_system
    }
    PERMISSIONS {
        uuid id PK
        text key UK "employee.view"
        text module
        bool is_sensitive
    }
    ROLE_PERMISSIONS {
        uuid role_id PK, FK
        uuid permission_id PK, FK
    }
    ROLE_ASSIGNMENTS {
        uuid id PK
        uuid user_id FK
        uuid role_id FK
        text scope_type "self | direct_reports | department | location | tenant"
        uuid scope_id "nullable"
        ts expires_at
    }
    SSO_CONNECTIONS {
        uuid id PK
        text protocol
        text issuer
        bool enforced
    }
    TENANT_FEATURES {
        uuid tenant_id PK
        text feature PK
        bool enabled
    }
    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        uuid tokenable_id FK
        jsonb abilities
        ts expires_at
    }
    MFA_RECOVERY_CODES {
        uuid id PK
        uuid user_id FK
        text code_hash
        ts used_at
    }
```

## 3. Organisation & Employee

```mermaid
erDiagram
    DEPARTMENTS ||--o{ DEPARTMENTS : "parent of"
    DEPARTMENTS ||--o{ POSITIONS : has
    JOB_GRADES ||--o{ POSITIONS : grades
    COST_CENTRES ||--o{ DEPARTMENTS : funds
    LOCATIONS ||--o{ EMPLOYEES : "based at"
    DEPARTMENTS ||--o{ EMPLOYEES : "current dept"
    EMPLOYEES ||--o{ EMPLOYEES : manages
    EMPLOYEES ||--o| DEPARTMENTS : heads
    USERS ||--o| EMPLOYEES : "logs in as"
    EMPLOYEES ||--|{ EMPLOYMENTS : "history of"
    POSITIONS ||--o{ EMPLOYMENTS : fills
    EMPLOYEES ||--o{ EMERGENCY_CONTACTS : has
    EMPLOYEES ||--o{ EMPLOYEE_ADDRESSES : has
    EMPLOYEES ||--o{ DOCUMENTS : owns

    DEPARTMENTS {
        uuid id PK
        uuid parent_id FK
        ltree path
        text code
        uuid head_employee_id FK
        uuid cost_centre_id FK
    }
    POSITIONS {
        uuid id PK
        uuid department_id FK
        uuid job_grade_id FK
        text title
        int headcount
    }
    JOB_GRADES {
        uuid id PK
        text code
        int rank
        bigint min_salary
        bigint max_salary
    }
    COST_CENTRES {
        uuid id PK
        text code
    }
    LOCATIONS {
        uuid id PK
        text country_code
        text timezone
    }
    USERS {
        uuid id PK
    }
    EMPLOYEES {
        uuid id PK
        uuid user_id FK "nullable, unique"
        text employee_number "UK per tenant"
        text first_name
        text last_name
        text status
        date hire_date
        uuid manager_id FK
        uuid department_id FK
        uuid location_id FK
        text national_id "encrypted"
        int version
    }
    EMPLOYMENTS {
        uuid id PK
        uuid employee_id FK
        uuid position_id FK
        uuid department_id FK
        text employment_type
        date effective_from
        date effective_to
    }
    EMERGENCY_CONTACTS {
        uuid id PK
        uuid employee_id FK
        text phone "encrypted"
    }
    EMPLOYEE_ADDRESSES {
        uuid id PK
        uuid employee_id FK
        jsonb address "encrypted"
    }
    DOCUMENTS {
        uuid id PK
        uuid employee_id FK
        text s3_key
        text checksum_sha256
        date expires_on
    }
```

## 4. Leave, Attendance & Compensation

```mermaid
erDiagram
    EMPLOYEES ||--o{ LEAVE_REQUESTS : submits
    EMPLOYEES ||--o{ LEAVE_BALANCE_LEDGER : "balance of"
    EMPLOYEES ||--o{ LEAVE_POLICY_ASSIGNMENTS : "covered by"
    LEAVE_TYPES ||--o{ LEAVE_POLICIES : "configured by"
    LEAVE_POLICIES ||--o{ LEAVE_POLICY_ASSIGNMENTS : "assigned via"
    LEAVE_TYPES ||--o{ LEAVE_REQUESTS : "type of"
    LEAVE_REQUESTS ||--|{ LEAVE_APPROVALS : "approval steps"
    LEAVE_REQUESTS ||--o{ LEAVE_BALANCE_LEDGER : "debits"
    LOCATIONS ||--o{ PUBLIC_HOLIDAYS : observes
    EMPLOYEES ||--o{ ATTENDANCE_RECORDS : records
    EMPLOYEES ||--o{ SALARIES : earns
    EMPLOYEES ||--o{ ALLOWANCES : receives

    EMPLOYEES {
        uuid id PK
    }
    LOCATIONS {
        uuid id PK
    }
    LEAVE_TYPES {
        uuid id PK
        text code
        bool is_paid
    }
    LEAVE_POLICIES {
        uuid id PK
        uuid leave_type_id FK
        text accrual_method
        numeric annual_entitlement
        numeric carry_over_max
    }
    LEAVE_POLICY_ASSIGNMENTS {
        uuid id PK
        uuid policy_id FK
        uuid employee_id FK
        date effective_from
    }
    LEAVE_REQUESTS {
        uuid id PK
        uuid employee_id FK
        uuid leave_type_id FK
        date start_date
        date end_date
        numeric days
        text status
        int version
    }
    LEAVE_APPROVALS {
        uuid id PK
        uuid leave_request_id FK
        int step
        uuid approver_id FK
        text status
    }
    LEAVE_BALANCE_LEDGER {
        uuid id PK
        uuid employee_id FK
        uuid leave_type_id FK
        text entry_type
        numeric amount "+credit / -debit"
        uuid leave_request_id FK
    }
    PUBLIC_HOLIDAYS {
        uuid id PK
        uuid location_id FK
        date date
    }
    ATTENDANCE_RECORDS {
        uuid id PK
        uuid employee_id FK
        date work_date "partition key"
        ts clock_in
        ts clock_out
    }
    SALARIES {
        uuid id PK
        uuid employee_id FK
        bigint amount_minor
        char currency
        date effective_from
        date effective_to
    }
    ALLOWANCES {
        uuid id PK
        uuid employee_id FK
        text type
        bigint amount_minor
    }
```

## 5. Platform Tables

```mermaid
erDiagram
    TENANTS ||--o{ AUDIT_EVENTS : scopes
    USERS ||--o{ AUDIT_EVENTS : "acts in"
    TENANTS ||--o{ OUTBOX_MESSAGES : emits

    TENANTS {
        uuid id PK
    }
    USERS {
        uuid id PK
    }
    AUDIT_EVENTS {
        bigint id PK
        uuid tenant_id FK
        ts occurred_at "partition key"
        uuid actor_id FK
        text action
        text subject_type
        uuid subject_id
        jsonb changes
        uuid request_id
        bytea prev_hash
        bytea hash
    }
    OUTBOX_MESSAGES {
        uuid id PK
        uuid tenant_id FK
        text event_type
        jsonb payload
        ts published_at
    }
```

## 6. Key Relationship Rules

| Relationship | Rule |
|---|---|
| User ↔ Employee | Optional 1:1. An employee may exist without a login (e.g. factory staff); a user may exist without an employee record (e.g. external auditor). |
| Employee → Manager | Self-reference. A database trigger rejects cycles (A manages B manages A). |
| Department tree | `parent_id` for integrity, `ltree path` for querying. Path is rebuilt in the same transaction when a department moves. |
| Employment / Salary history | Effective-dated, non-overlapping (exclusion constraint). The current row has `effective_to IS NULL`. |
| Leave balance | Derived from the ledger. The ledger is never updated or deleted; corrections are `reversal` entries. |
| Audit events | No foreign key to subjects: subjects may be anonymised or deleted, the audit record must survive. |
