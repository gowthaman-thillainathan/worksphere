# Employee Module

Employee profiles, the searchable directory, profile photos and the employee's sign-in account. Design: [docs/03 §2.5](../../../../../docs/03-database-design.md), [docs/05](../../../../../docs/05-api-structure.md), [docs/12](../../../../../docs/12-employee-module.md).

**Style:** pragmatic for now ([ADR-0004](../../../../../docs/adr/0004-pragmatic-ddd.md)). Lifecycle rules (transfer, terminate, rehire) will move this module to a full domain model.

| Layer | Contents |
|---|---|
| `Contracts` | `EmployeeDirectory` (is an employee active?) for other modules, e.g. Documents |
| `Domain` | `EmployeeStatus`, `EmploymentType`, `EmployeeNumber`, `EmployeeSearchCriteria`, `EmployeeSortField`, `EmployeeAccount`; `EmployeeInvariants` (unique work e-mail, valid manager without reporting cycles, consistent dates, department name); ports `EmployeeRepository`, `EmployeePhotoStorage`, `DepartmentLookup`, `EmployeeAccounts`; exceptions |
| `Application` | `CreateEmployee` (optionally with an account), `UpdateEmployee` (optimistic locking), `DeleteEmployee` (reason required), `RestoreEmployee`, `UpdateEmployeePhoto`, `RemoveEmployeePhoto`, `CreateEmployeeAccount`, `ChangeEmployeeRole`; queries `ListEmployees`, `GetEmployee`, `GetEmployeePhoto`, `GetHeadcount`, `GetEmployeeAccount`; listener `SyncDepartmentName` |
| `Infrastructure` | Eloquent model (encrypted PII, soft deletes), repository (search, per-field filters, cursor pagination), employee-number sequence, filesystem photo storage; adapters onto Organisation (`DepartmentDirectory`) and Identity (`AccountProvisioner`) |
| `Http` | Thin controllers, form requests (shared `EmployeeRules`, whitelisted list query, `DeleteEmployeeRequest`, `EmployeeRoleRequest`), `EmployeeResource` (PII by permission, `meta.permissions`), `EmployeeAccountResource` |

**Depends on:** `Access\Contracts` (permissions, `RoleNotFound`), `Audit\Contracts` (every change is audited in the same transaction), `Organisation\Contracts` (`DepartmentDirectory`, `DepartmentRenamed`), `Identity\Contracts` (`AccountProvisioner`).

## Decisions

- **Delete reason:** `DELETE /employees/{id}` requires `{"reason": "…"}` (5–500 characters). It is stored in `deletion_reason`, returned on deleted records and recorded as the `employee.deleted` change; restore clears it.
- **Department sorting** uses a copy of the name kept in sync by `DepartmentRenamed` ([ADR-0008](../../../../../docs/adr/0008-denormalised-department-name.md)).
- **Sign-in accounts** are created by invitation through Identity and need `user.manage` and `access.manage` ([ADR-0007](../../../../../docs/adr/0007-employee-sign-in-accounts.md)).

## Audit actions

`employee.created`, `employee.updated`, `employee.deleted` (with reason), `employee.restored`, `employee.photo_updated`, `employee.photo_removed`, `employee.account_created`, `employee.role_changed`. The Documents module records `document.uploaded` / `document.deleted` on the employee too, so the profile history shows them.
