import { MoreHorizontal, Plus, RotateCcw, Users } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { Link } from 'react-router';
import { toast } from 'sonner';
import { CursorPagination } from '@/components/CursorPagination';
import { DataTable, type DataTableColumn } from '@/components/data-table/DataTable';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { SearchInput } from '@/components/SearchInput';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import type { EmployeeSortField } from '@/services/employee-service';
import { useAuth } from '@/shared/auth/use-auth';
import { describeError } from '@/shared/lib/error-messages';
import { formatDate } from '@/shared/lib/format';
import type { Employee } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useEmployees, useRestoreEmployee } from '../api/employee-queries';
import { DeleteEmployeeDialog } from '../components/DeleteEmployeeDialog';
import { EmployeeAvatar } from '../components/EmployeeAvatar';
import { EmployeeFilters } from '../components/EmployeeFilters';
import { EmployeeStatusBadge } from '../components/EmployeeStatusBadge';
import { EMPLOYMENT_TYPE_LABELS } from '../lib/labels';
import { useEmployeeListQuery } from '../lib/list-query';

export default function EmployeeListPage() {
  useDocumentTitle('Employees');
  const { can } = useAuth();
  const { query, update } = useEmployeeListQuery();
  const employees = useEmployees(query);
  const restore = useRestoreEmployee();
  const [deleting, setDeleting] = useState<Employee | null>(null);
  const showingDeleted = query.trashed === 'only';

  const onSearch = useCallback((search: string) => update({ search: search || undefined }), [update]);

  const restoreEmployee = useCallback(
    (employee: Employee) =>
      restore.mutate(employee.id, {
        onSuccess: () => toast.success(`${employee.display_name} was restored.`),
        onError: (error) => toast.error(describeError(error).title, { description: describeError(error).description }),
      }),
    [restore],
  );

  const columns = useMemo<DataTableColumn<Employee, EmployeeSortField>[]>(
    () => [
      {
        id: 'name',
        header: 'Name',
        sortField: 'last_name',
        cell: (employee) => (
          <div className="flex items-center gap-3">
            <EmployeeAvatar employee={employee} />
            <div className="min-w-0">
              {showingDeleted ? (
                <span className="font-medium">{employee.display_name}</span>
              ) : (
                <Link to={paths.employee(employee.id)} className="font-medium hover:underline">
                  {employee.display_name}
                </Link>
              )}
              <div className="truncate text-xs text-muted-foreground">{employee.work_email}</div>
            </div>
          </div>
        ),
      },
      { id: 'number', header: 'Number', sortField: 'employee_number', hideBelow: 'md', cell: (employee) => <span className="font-mono text-xs">{employee.employee_number}</span> },
      { id: 'title', header: 'Job title', hideBelow: 'sm', cell: (employee) => employee.job_title },
      { id: 'status', header: 'Status', cell: (employee) => <EmployeeStatusBadge status={employee.status} /> },
      { id: 'type', header: 'Type', hideBelow: 'lg', cell: (employee) => EMPLOYMENT_TYPE_LABELS[employee.employment_type] },
      { id: 'hired', header: 'Hired', sortField: 'hire_date', hideBelow: 'md', cell: (employee) => formatDate(employee.hire_date) },
      {
        id: 'actions',
        header: 'Actions',
        className: 'w-12 text-right',
        cell: (employee) => <RowActions employee={employee} deleted={showingDeleted} onDelete={setDeleting} onRestore={restoreEmployee} />,
      },
    ],
    [showingDeleted, restoreEmployee],
  );

  const hasFilters = Boolean(query.search || query.statuses?.length || query.employmentTypes?.length || query.departmentId);

  return (
    <>
      <PageHeader
        title={showingDeleted ? 'Deleted employees' : 'Employees'}
        description={showingDeleted ? 'Restore employees who were deleted by mistake.' : 'Search and manage everyone in your organisation.'}
        actions={
          can(Permission.EmployeeCreate) && (
            <Button asChild>
              <Link to={paths.newEmployee}>
                <Plus aria-hidden="true" />
                Add employee
              </Link>
            </Button>
          )
        }
      />

      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <SearchInput label="Search employees" placeholder="Search by name, number or e-mail" value={query.search ?? ''} onSearch={onSearch} className="w-full lg:max-w-sm" />
        <EmployeeFilters query={query} onChange={update} />
      </div>

      {employees.isError ? (
        <ErrorState error={employees.error} onRetry={() => employees.refetch()} />
      ) : (
        <>
          <DataTable
            caption="Employees"
            columns={columns}
            rows={employees.data?.data}
            getRowId={(employee) => employee.id}
            isLoading={employees.isPending}
            sort={query.sort?.[0]}
            onSortChange={(sort) => update({ sort: [sort] })}
            empty={
              <EmptyState
                icon={Users}
                title={hasFilters ? 'No employees match your search' : showingDeleted ? 'No deleted employees' : 'No employees yet'}
                description={hasFilters ? 'Try other filters or clear the search.' : undefined}
              />
            }
          />
          <CursorPagination
            previousCursor={employees.data?.meta.prev_cursor}
            nextCursor={employees.data?.meta.next_cursor}
            onNavigate={(cursor) => update({ cursor })}
            perPage={query.perPage ?? 25}
            onPerPageChange={(perPage) => update({ perPage })}
            disabled={employees.isFetching}
          />
        </>
      )}

      <DeleteEmployeeDialog employee={deleting} onOpenChange={(open) => !open && setDeleting(null)} />
    </>
  );
}

interface RowActionsProps {
  employee: Employee;
  deleted: boolean;
  onDelete: (employee: Employee) => void;
  onRestore: (employee: Employee) => void;
}

function RowActions({ employee, deleted, onDelete, onRestore }: RowActionsProps) {
  const { can } = useAuth();
  const canDelete = can(Permission.EmployeeDelete);

  if (deleted) {
    return canDelete ? (
      <Button variant="outline" size="sm" onClick={() => onRestore(employee)}>
        <RotateCcw aria-hidden="true" />
        Restore
      </Button>
    ) : null;
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" aria-label={`Actions for ${employee.display_name}`}>
          <MoreHorizontal aria-hidden="true" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent>
        <DropdownMenuItem asChild>
          <Link to={paths.employee(employee.id)}>View details</Link>
        </DropdownMenuItem>
        {employee.meta.permissions.includes('update') && (
          <DropdownMenuItem asChild>
            <Link to={paths.editEmployee(employee.id)}>Edit</Link>
          </DropdownMenuItem>
        )}
        {employee.meta.permissions.includes('delete') && (
          <DropdownMenuItem className="text-destructive focus:text-destructive" onSelect={() => onDelete(employee)}>
            Delete
          </DropdownMenuItem>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
