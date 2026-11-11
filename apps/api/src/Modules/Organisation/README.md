# Organisation Module

Departments today; positions, job grades, locations and cost centres follow. Design: [docs/03 §2.4](../../../../../docs/03-database-design.md).

| Layer | Contents |
|---|---|
| `Contracts` (public) | `DepartmentDirectory` (existence checks for other modules), `DepartmentReferenceChecker` (implemented by modules that reference departments) |
| `Domain` | `DepartmentInvariants` (unique code, parent without cycles), repository port, exceptions |
| `Application` | `CreateDepartment`, `UpdateDepartment` (optimistic locking), `DeleteDepartment`, `ListDepartments`, `GetDepartment` |
| `Infrastructure` | Eloquent model and repository |
| `Http` | `/departments` CRUD (`organisation.view` / `organisation.manage`) |

**Deleting:** refused while the department has sub-departments, or while any module's `DepartmentReferenceChecker` reports it in use (e.g. it has employees). Organisation owns the interface and the other modules implement it, so Organisation never depends on them.

**Planned:** the `ltree` materialised path (docs/03) for subtree queries, needed by department-scoped RBAC.
