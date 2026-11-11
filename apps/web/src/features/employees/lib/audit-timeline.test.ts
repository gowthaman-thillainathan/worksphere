import { describe, expect, it } from 'vitest';
import type { AuditEvent } from '@/types/api';
import { groupByDay, presentEvent, type TimelineLookups } from './audit-timeline';

const lookups: TimelineLookups = {
  departmentName: (id) => ({ d1: 'Engineering', d2: 'Finance' })[id],
  roleName: (key) => ({ auditor: 'Auditor', hr_admin: 'HR Administrator' })[key],
};

function event(action: string, changes: Record<string, unknown[]> = {}, overrides: Partial<AuditEvent> = {}): AuditEvent {
  return {
    id: 1,
    type: 'audit_event',
    occurred_at: '2026-11-10T09:30:00Z',
    actor: { type: 'user', id: 'u1', name: 'Dilani Jayawardena' },
    action,
    subject: { type: 'employee', id: 'e1' },
    changes,
    ip_address: null,
    request_id: null,
    ...overrides,
  };
}

describe('audit timeline presenter', () => {
  it('names department changes and resolves department names', () => {
    const entry = presentEvent(event('employee.updated', { department_id: ['d1', 'd2'], job_title: ['Engineer', 'Lead Engineer'] }), lookups);

    expect(entry).toMatchObject({ kind: 'department', title: 'Department changed', actor: 'Dilani Jayawardena' });
    expect(entry.changes).toEqual([
      { field: 'Department', from: 'Engineering', to: 'Finance' },
      { field: 'Designation', from: 'Engineer', to: 'Lead Engineer' },
    ]);
  });

  it('describes deletions with their reason and role changes with role names', () => {
    expect(presentEvent(event('employee.deleted', { deletion_reason: [null, 'Duplicate record'] }), lookups)).toMatchObject({
      kind: 'deleted',
      description: 'Reason: Duplicate record',
      changes: [],
    });
    expect(presentEvent(event('employee.role_changed', { role: ['auditor', 'hr_admin'] }), lookups)).toMatchObject({
      kind: 'role',
      description: 'Auditor → HR Administrator',
    });
  });

  it('leaves the photo flag out of photo events', () => {
    expect(presentEvent(event('employee.photo_updated', { photo: [false, true] }), lookups)).toMatchObject({ kind: 'photo', title: 'Photo updated', changes: [] });
  });

  it('labels statuses, masks personal data and names system actors', () => {
    const entry = presentEvent(event('employee.updated', { status: ['active', 'on_leave'], phone: ['***', '***'] }, { actor: { type: 'system', id: null, name: null } }), lookups);

    expect(entry.actor).toBe('System');
    expect(entry.changes).toEqual([
      { field: 'Status', from: 'Active', to: 'On leave' },
      { field: 'Phone', from: 'hidden', to: 'hidden' },
    ]);
  });

  it('groups entries by local day, newest first', () => {
    const entries = [
      presentEvent(event('employee.updated', {}, { id: 3, occurred_at: '2026-11-10T15:00:00' }), lookups),
      presentEvent(event('employee.updated', {}, { id: 2, occurred_at: '2026-11-10T09:00:00' }), lookups),
      presentEvent(event('employee.created', {}, { id: 1, occurred_at: '2026-11-08T09:00:00' }), lookups),
    ];

    expect(groupByDay(entries).map((day) => [day.day, day.entries.map((entry) => entry.id)])).toEqual([
      ['2026-11-10', [3, 2]],
      ['2026-11-08', [1]],
    ]);
  });
});
