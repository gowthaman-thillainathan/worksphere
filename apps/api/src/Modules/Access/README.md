# Access Module

Role-based access control. Design: [docs/07](../../../../../docs/07-rbac.md).

**Style:** pragmatic ([ADR-0004](../../../../../docs/adr/0004-pragmatic-ddd.md)).

| Layer | Contents |
|---|---|
| `Contracts` (public) | `Permission` catalogue enum, `Authorizer`, `RoleAssigner`, `RoleNotFound` |
| `Domain` | `SystemRole` templates, repository interfaces, error codes |
| `Application` | `SyncPermissions`, `AssignRole`, `ListPermissions`, `ListRoles` |
| `Infrastructure` | Eloquent models and repositories, `CachedRoleBasedAuthorizer`, `access:sync-permissions` |
| `Http` | `GET /permissions`, `GET /roles` (require `access.manage`) |

Every `Permission` case is registered as a Laravel gate, so routes use `Authorize::using(Permission::X->value)` and policies use `Gate::authorize()`.

**Current scope:** roles apply tenant-wide. Scoped assignments (self, direct reports, department, location) arrive with the Employee module. Until then, only tenant-wide system roles ship, so no role can grant broader access than intended.
