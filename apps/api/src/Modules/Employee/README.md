# Employee Module

Employee profiles, the searchable directory and profile photos. Design: [docs/03 §2.5](../../../../../docs/03-database-design.md), [docs/05](../../../../../docs/05-api-structure.md).

**Style:** pragmatic for now ([ADR-0004](../../../../../docs/adr/0004-pragmatic-ddd.md)). Lifecycle rules (transfer, terminate, rehire) will move this module to a full domain model.

| Layer | Contents |
|---|---|
| `Domain` | `EmployeeStatus`, `EmploymentType`, `EmployeeNumber`, `EmployeeSearchCriteria`; `EmployeeInvariants` (unique work e-mail, valid manager without reporting cycles, consistent dates); `EmployeeRepository` / `EmployeePhotoStorage` ports; exceptions |
| `Application` | `CreateEmployee`, `UpdateEmployee` (optimistic locking), `DeleteEmployee`, `RestoreEmployee`, `UpdateEmployeePhoto`, `RemoveEmployeePhoto`; `ListEmployees`, `GetEmployee`, `GetEmployeePhoto` |
| `Infrastructure` | Eloquent model (encrypted PII, soft deletes), repository (search, filters, cursor pagination), employee-number sequence, filesystem photo storage |
| `Http` | Thin controllers, form requests (shared `EmployeeRules`, whitelisted list query), `EmployeeResource` (PII by permission, `meta.permissions`) |

**Depends on:** `Access\Contracts` (permissions), `Audit\Contracts` (every change is audited in the same transaction).
