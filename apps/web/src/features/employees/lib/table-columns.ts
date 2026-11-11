/**
 * Optional directory columns. The name column is always shown; the others
 * can be hidden, and the choice is remembered per browser.
 */
export const EMPLOYEE_COLUMNS = {
  number: { label: 'Employee ID', visibleByDefault: true },
  department: { label: 'Department', visibleByDefault: true },
  designation: { label: 'Designation', visibleByDefault: true },
  status: { label: 'Status', visibleByDefault: true },
  type: { label: 'Employment type', visibleByDefault: false },
  manager: { label: 'Manager', visibleByDefault: false },
  joined: { label: 'Join date', visibleByDefault: true },
  created: { label: 'Created', visibleByDefault: false },
} as const;

export type EmployeeColumnId = keyof typeof EMPLOYEE_COLUMNS;

export const EMPLOYEE_COLUMN_IDS = Object.keys(EMPLOYEE_COLUMNS) as EmployeeColumnId[];

export const DEFAULT_VISIBLE_COLUMNS: EmployeeColumnId[] = EMPLOYEE_COLUMN_IDS.filter((id) => EMPLOYEE_COLUMNS[id].visibleByDefault);

export const COLUMN_PREFERENCES_KEY = 'meridian.employees.columns';

export function isColumnList(value: unknown): value is EmployeeColumnId[] {
  return Array.isArray(value) && value.every((item) => typeof item === 'string' && item in EMPLOYEE_COLUMNS);
}
