import { formatDate, humanize } from '@/shared/lib/format';
import type { AuditEvent, EmployeeStatus, EmploymentType } from '@/types/api';
import { EMPLOYMENT_TYPE_LABELS, FIELD_LABELS, STATUS_LABELS } from './labels';

export type TimelineKind = 'created' | 'updated' | 'department' | 'deleted' | 'restored' | 'photo' | 'account' | 'role' | 'document';

export interface TimelineChange {
  field: string;
  from: string;
  to: string;
}

export interface TimelineEntry {
  id: number;
  kind: TimelineKind;
  title: string;
  description?: string;
  changes: TimelineChange[];
  actor: string;
  occurredAt: string;
}

/** Names the presenter cannot know by itself. */
export interface TimelineLookups {
  departmentName: (id: string) => string | undefined;
  roleName: (key: string) => string | undefined;
}

const MASK = '***';
const EMPTY = 'empty';
const DATE_FIELDS = new Set(['hire_date', 'termination_date', 'date_of_birth']);
/** Shown in the title or description of their event rather than as a change. */
const SUMMARY_FIELDS = new Set(['deletion_reason', 'role', 'document', 'photo']);

const ACTION_KINDS: Record<string, TimelineKind> = {
  'employee.created': 'created',
  'employee.updated': 'updated',
  'employee.deleted': 'deleted',
  'employee.restored': 'restored',
  'employee.photo_updated': 'photo',
  'employee.photo_removed': 'photo',
  'employee.account_created': 'account',
  'employee.role_changed': 'role',
  'document.uploaded': 'document',
  'document.deleted': 'document',
};

const TITLES: Record<string, string> = {
  'employee.created': 'Employee created',
  'employee.updated': 'Profile updated',
  'employee.deleted': 'Employee deleted',
  'employee.restored': 'Employee restored',
  'employee.photo_updated': 'Photo updated',
  'employee.photo_removed': 'Photo removed',
  'employee.account_created': 'Sign-in access granted',
  'employee.role_changed': 'Role changed',
  'document.uploaded': 'Document uploaded',
  'document.deleted': 'Document deleted',
};

type Change = [unknown, unknown];

function asChanges(event: AuditEvent): [string, Change][] {
  return Object.entries(event.changes ?? {}).map(([field, change]) => [field, (Array.isArray(change) ? change : [undefined, change]) as Change]);
}

function formatValue(field: string, value: unknown, lookups: TimelineLookups): string {
  if (value === null || value === undefined || value === '') return EMPTY;
  if (value === MASK) return 'hidden';

  const text = typeof value === 'string' ? value : JSON.stringify(value);

  switch (true) {
    case field === 'status':
      return STATUS_LABELS[text as EmployeeStatus] ?? humanize(text);
    case field === 'employment_type':
      return EMPLOYMENT_TYPE_LABELS[text as EmploymentType] ?? humanize(text);
    case field === 'department_id':
      return lookups.departmentName(text) ?? 'a department that no longer exists';
    case field === 'manager_id':
      return 'another employee';
    case field === 'role':
      return lookups.roleName(text) ?? humanize(text);
    case DATE_FIELDS.has(field):
      return formatDate(text);
    default:
      return text;
  }
}

const asText = (value: unknown): string => (typeof value === 'string' ? value : '');

function describe(event: AuditEvent, changes: [string, Change][], lookups: TimelineLookups): string | undefined {
  const value = (field: string, index: 0 | 1) => changes.find(([name]) => name === field)?.[1][index];

  switch (event.action) {
    case 'employee.deleted':
      return asText(value('deletion_reason', 1)) ? `Reason: ${asText(value('deletion_reason', 1))}` : undefined;
    case 'employee.account_created':
      return `Invited with the ${formatValue('role', value('role', 1), lookups)} role.`;
    case 'employee.role_changed':
      return `${formatValue('role', value('role', 0), lookups)} → ${formatValue('role', value('role', 1), lookups)}`;
    case 'document.uploaded':
      return asText(value('document', 1));
    case 'document.deleted':
      return asText(value('document', 0));
    default:
      return undefined;
  }
}

/** One audit event as a timeline entry with readable values. */
export function presentEvent(event: AuditEvent, lookups: TimelineLookups): TimelineEntry {
  const changes = asChanges(event);
  const departmentChanged = event.action === 'employee.updated' && changes.some(([field]) => field === 'department_id');
  const visibleChanges = event.action === 'employee.created' ? [] : changes.filter(([field]) => !SUMMARY_FIELDS.has(field));

  return {
    id: event.id,
    kind: departmentChanged ? 'department' : (ACTION_KINDS[event.action] ?? 'updated'),
    title: departmentChanged ? 'Department changed' : (TITLES[event.action] ?? humanize(event.action)),
    description: describe(event, changes, lookups),
    changes: visibleChanges.map(([field, [from, to]]) => ({
      field: FIELD_LABELS[field] ?? humanize(field),
      from: formatValue(field, from, lookups),
      to: formatValue(field, to, lookups),
    })),
    actor: event.actor.type === 'system' ? 'System' : (event.actor.name ?? 'A former user'),
    occurredAt: event.occurred_at,
  };
}

export interface TimelineDay {
  day: string;
  entries: TimelineEntry[];
}

/** Groups entries (newest first) by the local calendar day they happened on. */
export function groupByDay(entries: readonly TimelineEntry[]): TimelineDay[] {
  const days: TimelineDay[] = [];

  for (const entry of entries) {
    const date = new Date(entry.occurredAt);
    const day = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const last = days.at(-1);

    if (last?.day === day) last.entries.push(entry);
    else days.push({ day, entries: [entry] });
  }

  return days;
}
