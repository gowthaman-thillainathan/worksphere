import { formatDate } from '@/shared/lib/format';
import type { EmployeeStatus, EmploymentType } from '@/types/api';
import type { EmployeeFormValues } from './employee-form-schema';
import { EMPLOYMENT_TYPE_LABELS, STATUS_LABELS } from './labels';

const EMPTY = '—';
const DATE_FIELDS = new Set<keyof EmployeeFormValues>(['hire_date', 'termination_date', 'date_of_birth']);

/** A form value as people read it (labels, names, dates), for review and conflict screens. */
export function formatFormValue<Field extends keyof EmployeeFormValues>(
  field: Field,
  value: EmployeeFormValues[Field] | undefined,
  departmentName: (id: string) => string | undefined,
): string {
  if (value === null || value === undefined || value === '') return EMPTY;
  if (typeof value === 'object') return value.label;

  switch (true) {
    case field === 'status':
      return STATUS_LABELS[value as EmployeeStatus];
    case field === 'employment_type':
      return EMPLOYMENT_TYPE_LABELS[value as EmploymentType];
    case field === 'department_id':
      return departmentName(value) ?? EMPTY;
    case DATE_FIELDS.has(field):
      return formatDate(value);
    default:
      return value;
  }
}
