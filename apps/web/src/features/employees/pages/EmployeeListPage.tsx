import { Plus, Users } from 'lucide-react';
import { useCallback, useState } from 'react';
import { Link } from 'react-router';
import { CursorPagination } from '@/components/CursorPagination';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useDepartments } from '@/features/departments/department-queries';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { useMediaQuery } from '@/hooks/use-media-query';
import { paths } from '@/routes/paths';
import { useAuth } from '@/shared/auth/use-auth';
import type { Employee } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useEmployees } from '../api/employee-queries';
import { EmployeeBulkActions } from '../components/EmployeeBulkActions';
import { EmployeeCard } from '../components/EmployeeCard';
import { EmployeeColumnSelector } from '../components/EmployeeColumnSelector';
import { EmployeeDeleteDialog } from '../components/EmployeeDeleteDialog';
import { ActiveFilters, EmployeeFilters } from '../components/EmployeeFilters';
import { EmployeeRestoreDialog } from '../components/EmployeeRestoreDialog';
import { EmployeeSearch } from '../components/EmployeeSearch';
import { EmployeeTable } from '../components/EmployeeTable';
import { useColumnPreferences } from '../hooks/use-column-preferences';
import { useEmployeeSelection } from '../hooks/use-employee-selection';
import { activeFilterCount, DEFAULT_PER_PAGE, filterKey, useEmployeeListQuery } from '../lib/list-query';

/** Matches Tailwind's md breakpoint: cards below it, the table from it. */
const TABLE_LAYOUT_QUERY = '(min-width: 768px)';
const CARD_SKELETONS = 4;
const NO_EMPLOYEES: Employee[] = [];

export default function EmployeeListPage() {
  useDocumentTitle('Employees');
  const { can } = useAuth();
  const { query, update } = useEmployeeListQuery();
  const employees = useEmployees(query, { prefetchNext: true });
  const departments = useDepartments(can(Permission.OrganisationView));
  const columns = useColumnPreferences();
  const selection = useEmployeeSelection(filterKey(query));
  const showTable = useMediaQuery(TABLE_LAYOUT_QUERY);
  const [deleting, setDeleting] = useState<Employee[]>(NO_EMPLOYEES);
  const [restoring, setRestoring] = useState<Employee[]>(NO_EMPLOYEES);
  const canSelect = can(Permission.EmployeeDelete) || can(Permission.EmployeeExport);

  const onDelete = useCallback((employee: Employee) => setDeleting([employee]), []);
  const onRestore = useCallback((employee: Employee) => setRestoring([employee]), []);
  const departmentName = useCallback((id: string) => departments.data?.departments.find((department) => department.id === id)?.name, [departments.data]);

  const hasFilters = Boolean(query.search) || activeFilterCount(query) > 0;
  const rows = employees.data?.data;
  const empty = (
    <EmptyState
      icon={Users}
      title={hasFilters ? 'No employees match your search' : 'No employees yet'}
      description={hasFilters ? 'Try other filters or clear them.' : can(Permission.EmployeeCreate) ? 'Add the first employee to get started.' : undefined}
    />
  );

  return (
    <>
      <PageHeader
        title="Employees"
        description="Search, filter and manage everyone in your organisation."
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

      <div className="flex flex-col gap-3 md:flex-row md:items-center">
        <EmployeeSearch value={query.search ?? ''} onSearch={(search) => update({ search })} className="w-full md:max-w-sm" />
        <div className="flex gap-2 md:ml-auto">
          <EmployeeFilters query={query} onChange={update} />
          <EmployeeColumnSelector visible={columns.visible} onToggle={columns.toggle} onReset={columns.reset} />
        </div>
      </div>

      <ActiveFilters query={query} onChange={update} departmentName={departmentName} />

      <EmployeeBulkActions selected={selection.employees} onDelete={setDeleting} onRestore={setRestoring} onClear={selection.clear} />

      {employees.isError ? (
        <ErrorState error={employees.error} onRetry={() => employees.refetch()} />
      ) : (
        <>
          {showTable ? (
            <EmployeeTable
              employees={rows}
              isLoading={employees.isPending}
              visibleColumns={columns.visible}
              sort={query.sort?.[0]}
              onSortChange={(sort) => update({ sort: [sort] })}
              selection={canSelect ? selection : undefined}
              empty={empty}
              onDelete={onDelete}
              onRestore={onRestore}
            />
          ) : (
            <section aria-label="Employees" aria-busy={employees.isPending || undefined} className="grid gap-3">
              {employees.isPending && Array.from({ length: CARD_SKELETONS }, (_, index) => <Skeleton key={index} className="h-24" />)}
              {rows?.length === 0 && empty}
              {rows?.map((employee) => (
                <EmployeeCard
                  key={employee.id}
                  employee={employee}
                  selected={selection.isSelected(employee)}
                  onSelectedChange={canSelect ? selection.toggle : undefined}
                  onDelete={onDelete}
                  onRestore={onRestore}
                />
              ))}
            </section>
          )}
          <CursorPagination
            previousCursor={employees.data?.meta.prev_cursor}
            nextCursor={employees.data?.meta.next_cursor}
            onNavigate={(cursor) => update({ cursor })}
            perPage={query.perPage ?? DEFAULT_PER_PAGE}
            onPerPageChange={(perPage) => update({ perPage })}
            disabled={employees.isFetching}
          />
        </>
      )}

      <EmployeeDeleteDialog employees={deleting} onOpenChange={(open) => !open && setDeleting(NO_EMPLOYEES)} onDeleted={selection.clear} />
      <EmployeeRestoreDialog employees={restoring} onOpenChange={(open) => !open && setRestoring(NO_EMPLOYEES)} />
    </>
  );
}
