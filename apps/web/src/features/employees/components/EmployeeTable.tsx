import { useMemo, type ReactNode } from 'react';
import { DataTable, type DataTableColumn, type SortState } from '@/components/data-table/DataTable';
import type { EmployeeSortField } from '@/services/employee-service';
import { formatDate } from '@/shared/lib/format';
import type { Employee } from '@/types/api';
import type { EmployeeSelection } from '../hooks/use-employee-selection';
import { EMPLOYMENT_TYPE_LABELS } from '../lib/labels';
import { EMPLOYEE_COLUMNS, type EmployeeColumnId } from '../lib/table-columns';
import { EmployeeAvatar } from './EmployeeAvatar';
import { EmployeeNameLink } from './EmployeeNameLink';
import { EmployeeRowActions, type EmployeeActionHandlers } from './EmployeeRowActions';
import { EmployeeStatusBadge } from './EmployeeStatusBadge';

type Column = DataTableColumn<Employee, EmployeeSortField>;

const EMPTY_VALUE = '—';

/** Optional columns by id; the name and actions columns are always shown. */
const OPTIONAL_COLUMNS: Record<EmployeeColumnId, Omit<Column, 'id' | 'header'>> = {
  number: { sortField: 'employee_number', hideBelow: 'lg', cell: (employee) => <span className="font-mono text-xs">{employee.employee_number}</span> },
  department: { sortField: 'department', hideBelow: 'lg', cell: (employee) => employee.department_name ?? EMPTY_VALUE },
  designation: { cell: (employee) => employee.job_title },
  status: { cell: (employee) => <EmployeeStatusBadge status={employee.status} deleted={employee.deleted_at !== null} /> },
  type: { hideBelow: 'lg', cell: (employee) => EMPLOYMENT_TYPE_LABELS[employee.employment_type] },
  manager: { hideBelow: 'lg', cell: (employee) => employee.manager?.display_name ?? EMPTY_VALUE },
  joined: { sortField: 'hire_date', cell: (employee) => formatDate(employee.hire_date) },
  created: { sortField: 'created_at', hideBelow: 'lg', cell: (employee) => formatDate(employee.created_at) },
};

interface EmployeeTableProps extends EmployeeActionHandlers {
  employees: Employee[] | undefined;
  isLoading: boolean;
  visibleColumns: readonly EmployeeColumnId[];
  sort: SortState<EmployeeSortField> | undefined;
  onSortChange: (sort: SortState<EmployeeSortField>) => void;
  selection?: EmployeeSelection;
  empty: ReactNode;
}

/** The directory as a sortable, selectable table (tablet and desktop). */
export function EmployeeTable({ employees, isLoading, visibleColumns, sort, onSortChange, selection, empty, onDelete, onRestore }: EmployeeTableProps) {
  const columns = useMemo<Column[]>(
    () => [
      {
        id: 'name',
        header: 'Name',
        sortField: 'last_name',
        cell: (employee) => (
          <div className="flex items-center gap-3">
            <EmployeeAvatar employee={employee} />
            <div className="min-w-0">
              <EmployeeNameLink employee={employee} />
              <div className="truncate text-xs text-muted-foreground">{employee.work_email}</div>
            </div>
          </div>
        ),
      },
      ...visibleColumns.map((id) => ({ id, header: EMPLOYEE_COLUMNS[id].label, ...OPTIONAL_COLUMNS[id] })),
      {
        id: 'actions',
        header: 'Actions',
        className: 'w-12 text-right',
        cell: (employee) => <EmployeeRowActions employee={employee} onDelete={onDelete} onRestore={onRestore} />,
      },
    ],
    [visibleColumns, onDelete, onRestore],
  );

  return (
    <DataTable
      caption="Employees"
      columns={columns}
      rows={employees}
      getRowId={(employee) => employee.id}
      isLoading={isLoading}
      sort={sort}
      onSortChange={onSortChange}
      empty={empty}
      selection={
        selection && {
          isSelected: selection.isSelected,
          onToggle: selection.toggle,
          onToggleAll: selection.toggleMany,
          rowLabel: (employee) => `Select ${employee.display_name}`,
        }
      }
    />
  );
}
