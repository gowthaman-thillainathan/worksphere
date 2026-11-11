/**
 * Permission keys as defined by the API's catalogue (GET /permissions).
 * The UI only decides what to *show* from the permissions in /me; the API
 * enforces every rule. No role names appear in the frontend.
 */
export const Permission = {
  EmployeeView: 'employee.view',
  EmployeeCreate: 'employee.create',
  EmployeeUpdate: 'employee.update',
  EmployeeDelete: 'employee.delete',
  EmployeeViewSensitive: 'employee.view.sensitive',
  EmployeeUpdateSensitive: 'employee.update.sensitive',
  EmployeeExport: 'employee.export',
  DocumentView: 'document.view',
  DocumentUpload: 'document.upload',
  DocumentDelete: 'document.delete',
  UserManage: 'user.manage',
  OrganisationView: 'organisation.view',
  OrganisationManage: 'organisation.manage',
  AccessManage: 'access.manage',
  AuditView: 'audit.view',
} as const;

export type PermissionKey = (typeof Permission)[keyof typeof Permission];
