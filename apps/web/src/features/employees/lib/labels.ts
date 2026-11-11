import type { BadgeVariant } from '@/components/ui/badge';
import { EmployeeStatus, EmploymentType } from '@/types/api';

export const STATUS_LABELS: Record<EmployeeStatus, string> = {
  [EmployeeStatus.pre_boarding]: 'Pre-boarding',
  [EmployeeStatus.active]: 'Active',
  [EmployeeStatus.on_leave]: 'On leave',
  [EmployeeStatus.notice]: 'Notice period',
  [EmployeeStatus.terminated]: 'Terminated',
};

export const STATUS_VARIANTS: Record<EmployeeStatus, BadgeVariant> = {
  [EmployeeStatus.pre_boarding]: 'secondary',
  [EmployeeStatus.active]: 'success',
  [EmployeeStatus.on_leave]: 'warning',
  [EmployeeStatus.notice]: 'warning',
  [EmployeeStatus.terminated]: 'destructive',
};

export const EMPLOYMENT_TYPE_LABELS: Record<EmploymentType, string> = {
  [EmploymentType.full_time]: 'Full time',
  [EmploymentType.part_time]: 'Part time',
  [EmploymentType.contract]: 'Contract',
  [EmploymentType.intern]: 'Intern',
};

export const EMPLOYEE_STATUSES = Object.values(EmployeeStatus);
export const EMPLOYMENT_TYPES = Object.values(EmploymentType);
