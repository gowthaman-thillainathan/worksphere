# 0008 — Copy of the department name on employees for sorting

- Status: Accepted
- Date: 2026-11-11

## Context

The directory must sort by department. Departments belong to the Organisation module; employees only hold `department_id`. Sorting by name needs the name in the same query, and cursor pagination needs a non-null, indexed sort key with a unique tie-breaker.

Joining `departments` from the Employee repository would make Employee read another module's table, which the module rules forbid (only the future Reporting module may join across modules).

## Decision

- `employees.department_name` (`varchar(150) NOT NULL DEFAULT ''`) holds a copy of the department's name; `''` means no department. Index `(department_name, id)`.
- Employee writes it on create and when `department_id` changes, from its `DepartmentLookup` port (Organisation's `DepartmentDirectory::nameOf`).
- Organisation publishes `Contracts\Events\DepartmentRenamed` **inside** the renaming transaction; Employee's synchronous listener `SyncDepartmentName` updates the copies (deleted employees included) in the same transaction, without bumping employee versions.
- The API sort field is `department`, mapped to the column by `EmployeeSortField::column()`. The resource also returns `department_name`, so lists show names without an extra request.

## Consequences

### Positive
- Correct, index-backed cursor pagination by department, with no cross-module joins.
- Directory rows carry the department name, saving the client a lookup.

### Negative
- A rename updates every employee of that department in the renaming transaction. That is fine at our size (thousands of rows per department); if it ever becomes slow, the listener can move to a queued job, with a short period of eventual consistency.
- Employees without a department sort first in ascending order (empty string). Acceptable, and documented in the OpenAPI spec.

## Alternatives considered

- **Join in the repository:** simplest, but it violates module boundaries.
- **Sort in the client:** wrong across pages.
- **Sort by `department_id`:** meaningless to users.
- **Materialised view in a Reporting module:** right for analytics, too heavy for the directory today.
