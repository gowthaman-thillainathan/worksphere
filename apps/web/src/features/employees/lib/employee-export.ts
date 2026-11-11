import { toCsv, type CsvColumn } from '@/shared/lib/csv';
import { downloadFile } from '@/shared/lib/download';
import type { Employee } from '@/types/api';
import { EMPLOYMENT_TYPE_LABELS, STATUS_LABELS } from './labels';

const CSV_MIME_TYPE = 'text/csv;charset=utf-8';

/** Directory fields only: personal data is never exported from the list. */
export const EMPLOYEE_EXPORT_COLUMNS: readonly CsvColumn<Employee>[] = [
  { header: 'Employee ID', value: (employee) => employee.employee_number },
  { header: 'First name', value: (employee) => employee.first_name },
  { header: 'Last name', value: (employee) => employee.last_name },
  { header: 'Work e-mail', value: (employee) => employee.work_email },
  { header: 'Designation', value: (employee) => employee.job_title },
  { header: 'Department', value: (employee) => employee.department_name },
  { header: 'Manager', value: (employee) => employee.manager?.display_name },
  { header: 'Status', value: (employee) => STATUS_LABELS[employee.status] },
  { header: 'Employment type', value: (employee) => EMPLOYMENT_TYPE_LABELS[employee.employment_type] },
  { header: 'Join date', value: (employee) => employee.hire_date },
  { header: 'Deleted', value: (employee) => (employee.deleted_at ? 'Yes' : 'No') },
];

export function exportEmployees(employees: readonly Employee[], today: Date = new Date()): void {
  const stamp = today.toISOString().slice(0, 10);

  downloadFile(toCsv(employees, EMPLOYEE_EXPORT_COLUMNS), `employees-${stamp}.csv`, CSV_MIME_TYPE);
}
