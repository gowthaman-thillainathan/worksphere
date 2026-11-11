import type { BadgeVariant } from '@/components/ui/badge';
import type { EmployeeRecordState } from '@/services/employee-service';
import { DocumentCategory, EmployeeStatus, EmploymentType } from '@/types/api';

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

/** "Active / Inactive" in the directory: inactive records are soft-deleted ones. */
export const RECORD_STATE_LABELS: Record<EmployeeRecordState, string> = {
  active: 'Active records',
  deleted: 'Deleted records',
  all: 'All records',
};

export const DOCUMENT_CATEGORY_LABELS: Record<DocumentCategory, string> = {
  [DocumentCategory.contract]: 'Contract',
  [DocumentCategory.id_proof]: 'ID proof',
  [DocumentCategory.certificate]: 'Certificate',
  [DocumentCategory.other]: 'Other',
};

/** Field names as people say them, for history and conflict messages. */
export const FIELD_LABELS: Record<string, string> = {
  employee_number: 'Employee ID',
  first_name: 'First name',
  last_name: 'Last name',
  preferred_name: 'Preferred name',
  work_email: 'Work e-mail',
  job_title: 'Designation',
  employment_type: 'Employment type',
  status: 'Status',
  hire_date: 'Join date',
  termination_date: 'Termination date',
  manager_id: 'Manager',
  manager: 'Manager',
  department_id: 'Department',
  personal_email: 'Personal e-mail',
  phone: 'Phone',
  date_of_birth: 'Date of birth',
  deletion_reason: 'Reason',
  role: 'Role',
  document: 'Document',
};

export const EMPLOYEE_STATUSES = Object.values(EmployeeStatus);
export const EMPLOYMENT_TYPES = Object.values(EmploymentType);
export const RECORD_STATES = Object.keys(RECORD_STATE_LABELS) as EmployeeRecordState[];
export const DOCUMENT_CATEGORIES = Object.values(DocumentCategory);
