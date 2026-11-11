import type { AuditEvent, Department, Document, Employee, Role, User } from '@/types/api';

export const user: User = {
  id: '0193a4c9-0000-7000-8000-000000000001',
  type: 'user',
  name: 'Ayesha Fernando',
  email: 'ayesha@meridian.test',
  email_verified: true,
  status: 'active',
  last_login_at: '2026-11-10T08:00:00Z',
  created_at: '2026-01-01T00:00:00Z',
};

let sequence = 0;

export function employee(overrides: Partial<Employee> = {}): Employee {
  sequence += 1;
  const id = `0193a4c2-0000-7000-8000-${String(sequence).padStart(12, '0')}`;

  return {
    id,
    type: 'employee',
    employee_number: `EMP-${String(sequence).padStart(6, '0')}`,
    first_name: 'Nimal',
    last_name: 'Perera',
    preferred_name: null,
    display_name: 'Nimal Perera',
    work_email: `nimal${sequence}@meridian.test`,
    job_title: 'Engineer',
    employment_type: 'full_time',
    status: 'active',
    hire_date: '2022-04-01',
    termination_date: null,
    manager: null,
    department_id: null,
    department_name: null,
    has_account: false,
    photo_url: null,
    created_at: '2026-11-10T08:00:00Z',
    updated_at: '2026-11-10T08:00:00Z',
    deleted_at: null,
    deletion_reason: null,
    meta: { version: 1, permissions: ['update', 'delete'] },
    ...overrides,
  };
}

export function department(overrides: Partial<Department> = {}): Department {
  return {
    id: '0193a4c3-0000-7000-8000-000000000001',
    type: 'department',
    code: 'ENG',
    name: 'Engineering',
    description: null,
    parent: null,
    created_at: '2026-11-10T08:00:00Z',
    updated_at: '2026-11-10T08:00:00Z',
    meta: { version: 1, permissions: ['update', 'delete'] },
    ...overrides,
  };
}

export const ALL_PERMISSIONS = [
  'employee.view',
  'employee.create',
  'employee.update',
  'employee.delete',
  'employee.view.sensitive',
  'employee.update.sensitive',
  'employee.export',
  'document.view',
  'document.upload',
  'document.delete',
  'organisation.view',
  'organisation.manage',
  'access.manage',
  'user.manage',
  'audit.view',
];

export function auditEvent(overrides: Partial<AuditEvent> = {}): AuditEvent {
  return {
    id: 1,
    type: 'audit_event',
    occurred_at: '2026-11-10T09:30:00Z',
    actor: { type: 'user', id: user.id, name: user.name },
    action: 'employee.updated',
    subject: { type: 'employee', id: '0193a4c2-0000-7000-8000-000000000001' },
    changes: {},
    ip_address: '127.0.0.1',
    request_id: null,
    ...overrides,
  };
}

export function role(overrides: Partial<Role> = {}): Role {
  return {
    id: '0193a4c4-0000-7000-8000-000000000001',
    key: 'auditor',
    name: 'Auditor',
    description: null,
    is_system: true,
    permissions: ['employee.view', 'audit.view'],
    ...overrides,
  };
}

export function document(overrides: Partial<Document> = {}): Document {
  return {
    id: '0193a4c5-0000-7000-8000-000000000001',
    type: 'document',
    employee_id: '0193a4c2-0000-7000-8000-000000000001',
    title: 'Employment contract',
    category: 'contract',
    confidential: false,
    file_name: 'contract.pdf',
    mime_type: 'application/pdf',
    size_bytes: 120_000,
    expires_on: null,
    download_url: 'http://localhost/api/v1/documents/0193a4c5-0000-7000-8000-000000000001/download',
    uploaded_by: user.id,
    created_at: '2026-11-10T08:00:00Z',
    ...overrides,
  };
}
