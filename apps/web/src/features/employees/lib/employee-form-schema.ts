import { z } from 'zod';
import type { Employee, EmployeeCreate, EmployeeUpdate } from '@/types/api';
import { EmployeeStatus, EmploymentType } from '@/types/api';

const DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;
const PHONE_PATTERN = /^\+?[0-9 ()-]{7,20}$/;
const MINIMUM_AGE_YEARS = 16;

const optionalDate = z.string().refine((value) => value === '' || DATE_PATTERN.test(value), 'Use the format YYYY-MM-DD.');

/** YYYY-MM-DD in the user's time zone (toISOString would use UTC and can be off by a day). */
function localIsoDate(date: Date): string {
  const pad = (value: number) => String(value).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function yearsAgo(years: number): string {
  const date = new Date();
  date.setFullYear(date.getFullYear() - years);

  return localIsoDate(date);
}

/**
 * Client-side mirror of the API's rules for quick feedback. The API remains
 * authoritative: its 409/422 answers are shown on the same fields.
 */
export const employeeFormSchema = z
  .object({
    first_name: z.string().trim().min(1, 'Enter a first name.').max(100),
    last_name: z.string().trim().min(1, 'Enter a last name.').max(100),
    preferred_name: z.string().trim().max(100),
    work_email: z.string().trim().min(1, 'Enter a work e-mail.').email('Enter a valid e-mail address.').max(255),
    job_title: z.string().trim().min(1, 'Enter a job title.').max(150),
    employment_type: z.nativeEnum(EmploymentType),
    status: z.nativeEnum(EmployeeStatus),
    hire_date: z.string().regex(DATE_PATTERN, 'Choose a hire date.'),
    termination_date: optionalDate,
    manager: z.object({ id: z.string(), label: z.string() }).nullable(),
    department_id: z.string(),
    personal_email: z.string().trim().max(255).refine((value) => value === '' || z.string().email().safeParse(value).success, 'Enter a valid e-mail address.'),
    phone: z.string().trim().refine((value) => value === '' || PHONE_PATTERN.test(value), 'Enter a valid phone number.'),
    date_of_birth: optionalDate.refine((value) => value === '' || value < yearsAgo(MINIMUM_AGE_YEARS), `Employees must be at least ${MINIMUM_AGE_YEARS}.`),
  })
  .superRefine((values, context) => {
    if (values.status === EmployeeStatus.terminated && values.termination_date === '') {
      context.addIssue({ code: 'custom', path: ['termination_date'], message: 'Enter the termination date.' });
    }

    if (values.termination_date !== '' && values.termination_date < values.hire_date) {
      context.addIssue({ code: 'custom', path: ['termination_date'], message: 'Cannot be before the hire date.' });
    }
  });

export type EmployeeFormValues = z.infer<typeof employeeFormSchema>;

/** Sign-in access chosen in the create wizard (needs user.manage and access.manage). */
const accountSchema = z
  .object({
    grant_access: z.boolean(),
    account_role: z.string(),
  })
  .superRefine((values, context) => {
    if (values.grant_access && values.account_role === '') {
      context.addIssue({ code: 'custom', path: ['account_role'], message: 'Choose a role.' });
    }
  });

export const employeeWizardSchema = employeeFormSchema.and(accountSchema);

export type EmployeeWizardValues = z.infer<typeof employeeWizardSchema>;

/** The create wizard's steps and the fields each one owns, so a step validates only its fields. */
export const WIZARD_STEPS = [
  {
    key: 'personal',
    title: 'Personal information',
    description: 'Name and contact details',
    fields: ['first_name', 'last_name', 'preferred_name', 'work_email', 'phone', 'personal_email', 'date_of_birth'],
  },
  {
    key: 'employment',
    title: 'Employment',
    description: 'Role in the organisation',
    fields: ['job_title', 'department_id', 'manager', 'employment_type', 'hire_date'],
  },
  {
    key: 'account',
    title: 'Account',
    description: 'Status and sign-in access',
    fields: ['status', 'termination_date', 'grant_access', 'account_role'],
  },
  { key: 'review', title: 'Review', description: 'Check and create', fields: [] },
] as const satisfies readonly { key: string; title: string; description: string; fields: readonly (keyof EmployeeWizardValues)[] }[];

export type WizardField = (typeof WIZARD_STEPS)[number]['fields'][number];

/** Index of the step that owns the field, e.g. to show a server error. */
export function stepOfField(field: string): number {
  return WIZARD_STEPS.findIndex((step) => (step.fields as readonly string[]).includes(field));
}

export const EMPLOYEE_FORM_FIELDS = [
  'first_name', 'last_name', 'preferred_name', 'work_email', 'job_title', 'employment_type', 'status',
  'hire_date', 'termination_date', 'department_id', 'personal_email', 'phone', 'date_of_birth',
] as const satisfies readonly (keyof EmployeeFormValues)[];

export const SENSITIVE_FIELDS = ['personal_email', 'phone', 'date_of_birth'] as const;

const blankToNull = (value: string) => (value.trim() === '' ? null : value.trim());

export function emptyEmployeeForm(): EmployeeFormValues {
  return {
    first_name: '', last_name: '', preferred_name: '', work_email: '', job_title: '',
    employment_type: EmploymentType.full_time, status: EmployeeStatus.active,
    hire_date: localIsoDate(new Date()), termination_date: '', manager: null, department_id: '',
    personal_email: '', phone: '', date_of_birth: '',
  };
}

export function emptyWizardForm(): EmployeeWizardValues {
  return { ...emptyEmployeeForm(), grant_access: false, account_role: '' };
}

export function employeeToForm(employee: Employee): EmployeeFormValues {
  return {
    first_name: employee.first_name,
    last_name: employee.last_name,
    preferred_name: employee.preferred_name ?? '',
    work_email: employee.work_email,
    job_title: employee.job_title,
    employment_type: employee.employment_type,
    status: employee.status,
    hire_date: employee.hire_date,
    termination_date: employee.termination_date ?? '',
    manager: employee.manager ? { id: employee.manager.id, label: employee.manager.display_name } : null,
    department_id: employee.department_id ?? '',
    personal_email: employee.personal_email ?? '',
    phone: employee.phone ?? '',
    date_of_birth: employee.date_of_birth ?? '',
  };
}

/** Form values → API payload. `includeSensitive` is false for users who may not write personal data. */
export function formToPayload(values: EmployeeFormValues, includeSensitive: boolean): EmployeeCreate {
  return {
    first_name: values.first_name.trim(),
    last_name: values.last_name.trim(),
    preferred_name: blankToNull(values.preferred_name),
    work_email: values.work_email.trim(),
    job_title: values.job_title.trim(),
    employment_type: values.employment_type,
    status: values.status,
    hire_date: values.hire_date,
    termination_date: blankToNull(values.termination_date),
    manager_id: values.manager?.id ?? null,
    department_id: blankToNull(values.department_id),
    ...(includeSensitive && {
      personal_email: blankToNull(values.personal_email),
      phone: blankToNull(values.phone),
      date_of_birth: blankToNull(values.date_of_birth),
    }),
  };
}

/** Wizard values → API payload, with the sign-in account when it was requested. */
export function wizardToPayload(values: EmployeeWizardValues, includeSensitive: boolean): EmployeeCreate {
  return {
    ...formToPayload(values, includeSensitive),
    ...(values.grant_access && { account: { role: values.account_role } }),
  };
}

/** PATCH only what changed, so concurrent edits of other fields are not overwritten. */
export function changedFields(before: EmployeeCreate, after: EmployeeCreate): EmployeeUpdate {
  return Object.fromEntries(
    Object.entries(after).filter(([key, value]) => before[key as keyof EmployeeCreate] !== value),
  );
}
