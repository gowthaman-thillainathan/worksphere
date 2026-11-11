/**
 * Re-exports of the generated API types the application works with, so
 * features never import from the generated folder directly.
 */
export type {
  AuditEvent,
  Department,
  DepartmentCreate,
  Document,
  DepartmentUpdate,
  Employee,
  EmployeeAccount,
  EmployeeCreate,
  EmployeeUpdate,
  Headcount,
  ListAuditEvents200,
  ListEmployees200,
  Permission as PermissionDefinition,
  Role,
  User,
} from '@/services/api/generated/model';
export { DocumentCategory, EmployeeStatus, EmploymentType } from '@/services/api/generated/model';
