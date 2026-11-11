# 0007 — Employee sign-in accounts by invitation, owned by Identity

- Status: Accepted
- Date: 2026-11-11

## Context

Employees are HR records (Employee module); sign-in accounts and roles belong to Identity and Access. HR needs to give a new hire access to Meridian while creating them, and to change that access later, and every change must show on the employee's history.

Constraints:
- Modules may only use each other's `Contracts` (enforced by architecture tests).
- An administrator must never know or choose another person's password.
- Granting access is a security decision, separate from HR data entry (separation of duties, docs/07).

## Decision

- **Link:** `employees.user_id` (nullable, unique, `ON DELETE SET NULL`). One account per employee, one employee per account.
- **Contract:** Identity publishes `AccountProvisioner` (`invite`, `changeRole`, `find`) and the `AccountSummary` DTO. Employee depends on its own port `EmployeeAccounts`, adapted onto that contract in `Infrastructure/Identity`, and maps Access's `RoleNotFound` to its own `invalid_role` (422).
- **Invitation, not passwords:** the account is created without a password; the e-mail carries a single-use password-reset token (`/reset-password?…&invited=1`). Setting the password also verifies the address. The notification is queued *after commit*, so a rolled-back hire never gets an invitation.
- **One role per account here.** `RoleAssigner::replace()` swaps it atomically and clears the cached permission set.
- **Permissions:** creating an account or changing its role requires `user.manage` **and** `access.manage`, checked in one place (`AuthorizesAccountManagement`) for `POST /employees` with `account`, `POST /employees/{id}/account` and `PUT /employees/{id}/account/role`. `meta.permissions` exposes `manage_account` so the UI shows the controls only to those users.
- **History:** `employee.account_created` and `employee.role_changed` are recorded on the employee, in the same transaction.

## Consequences

### Positive
- Employee never touches users or roles directly; Identity can change how accounts work (SSO, MFA) behind the contract.
- No shared or e-mailed passwords; the invitation doubles as e-mail verification.
- Creating an employee with access is all-or-nothing.

### Negative
- An HR administrator alone cannot grant access; a person (or role) with `user.manage` + `access.manage` must. This is intended, but needs explaining to customers.
- Invitation links expire with the password-reset lifetime (30 minutes by default); after that the employee uses "Forgot your password?".
- Changing an employee's work e-mail does not change their sign-in e-mail yet, and deleting an employee does not disable their account yet. Both are planned as follow-ups.

## Alternatives considered

- **Employee module writes `users` directly:** fastest, but it breaks module boundaries and puts credentials logic in HR code.
- **Admin sets an initial password:** the admin would know the password and it would travel outside the system. Rejected.
- **Separate "Users" screen only:** keeps concerns apart but makes onboarding two disconnected tasks; the contract keeps them apart in code while the UI offers both together.
