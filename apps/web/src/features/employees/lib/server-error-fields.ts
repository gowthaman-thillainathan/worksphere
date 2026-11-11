import type { EmployeeFormValues, EmployeeWizardValues } from './employee-form-schema';

/*
| Where the API's errors go on the employee forms (useFormServerErrors).
| The create wizard adds the sign-in account's role to the edit form's fields.
*/

/** Every field that can receive a server message. */
export const SERVER_ERROR_FIELDS = [
  'first_name', 'last_name', 'preferred_name', 'work_email', 'job_title', 'employment_type', 'status', 'hire_date',
  'termination_date', 'department_id', 'manager', 'personal_email', 'phone', 'date_of_birth',
] as const satisfies readonly (keyof EmployeeFormValues)[];

/** API field names that differ from the form's. */
export const SERVER_ERROR_ALIASES = { manager_id: 'manager' } as const satisfies Record<string, keyof EmployeeFormValues>;

/** Business errors (409/422) that belong to one field. */
export const SERVER_ERROR_CODES = {
  work_email_in_use: 'work_email',
  email_already_taken: 'work_email',
  invalid_manager: 'manager',
  invalid_department: 'department_id',
  invalid_employment_dates: 'termination_date',
} as const satisfies Record<string, keyof EmployeeFormValues>;

export const WIZARD_ERROR_FIELDS = [...SERVER_ERROR_FIELDS, 'account_role'] as const satisfies readonly (keyof EmployeeWizardValues)[];

export const WIZARD_ERROR_ALIASES = { ...SERVER_ERROR_ALIASES, 'account.role': 'account_role' } as const satisfies Record<string, keyof EmployeeWizardValues>;

export const WIZARD_ERROR_CODES = { ...SERVER_ERROR_CODES, invalid_role: 'account_role' } as const satisfies Record<string, keyof EmployeeWizardValues>;

/** The form field a server error belongs to, if any. */
export function wizardErrorField(fieldErrors: Record<string, string[]>, code: string): string | undefined {
  const apiField = Object.keys(fieldErrors)[0];

  if (apiField) return WIZARD_ERROR_ALIASES[apiField as keyof typeof WIZARD_ERROR_ALIASES] ?? apiField;

  return WIZARD_ERROR_CODES[code as keyof typeof WIZARD_ERROR_CODES];
}
