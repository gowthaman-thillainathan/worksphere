import type { Department, Employee, User } from '@/types/api';

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
    photo_url: null,
    created_at: '2026-11-10T08:00:00Z',
    updated_at: '2026-11-10T08:00:00Z',
    deleted_at: null,
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
  'organisation.view',
  'organisation.manage',
  'access.manage',
  'audit.view',
];
