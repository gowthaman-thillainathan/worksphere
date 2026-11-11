import { Building2, MoreHorizontal, Plus } from 'lucide-react';
import { useId, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { DataTable, type DataTableColumn } from '@/components/data-table/DataTable';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Drawer } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { useAuth } from '@/shared/auth/use-auth';
import { describeError } from '@/shared/lib/error-messages';
import type { Department } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useDeleteDepartment, useDepartments, useSaveDepartment } from './department-queries';
import { DepartmentForm, type DepartmentFormValues } from './DepartmentForm';

type Editing = { mode: 'create' } | { mode: 'edit'; department: Department } | null;

export default function DepartmentsPage() {
  useDocumentTitle('Departments');
  const formId = useId();
  const { can } = useAuth();
  const departments = useDepartments();
  const save = useSaveDepartment();
  const remove = useDeleteDepartment();
  const [editing, setEditing] = useState<Editing>(null);
  const [deleting, setDeleting] = useState<Department | null>(null);
  const canManage = can(Permission.OrganisationManage);

  const onSubmit = async (values: DepartmentFormValues) => {
    const department = editing?.mode === 'edit' ? editing.department : undefined;
    const saved = await save.mutateAsync({
      department,
      values: { ...values, description: values.description || null, parent_id: values.parent_id || null },
    });
    toast.success(department ? `${saved.name} was updated.` : `${saved.name} was created.`);
    setEditing(null);
  };

  const confirmDelete = () => {
    if (!deleting) return;

    remove.mutate(deleting, {
      onSuccess: () => toast.success(`${deleting.name} was deleted.`),
      onError: (error) => toast.error(describeError(error).title, { description: describeError(error).description }),
      onSettled: () => setDeleting(null),
    });
  };

  const columns = useMemo<DataTableColumn<Department>[]>(
    () => [
      {
        id: 'name',
        header: 'Name',
        cell: (department) => (
          <div>
            <div className="font-medium">{department.name}</div>
            {department.description && <div className="line-clamp-1 text-xs text-muted-foreground">{department.description}</div>}
          </div>
        ),
      },
      { id: 'code', header: 'Code', cell: (department) => <Badge variant="outline" className="font-mono">{department.code}</Badge> },
      { id: 'parent', header: 'Parent', hideBelow: 'sm', cell: (department) => department.parent?.name ?? '—' },
      {
        id: 'actions',
        header: 'Actions',
        className: 'w-12 text-right',
        cell: (department) =>
          department.meta.permissions.length > 0 && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" aria-label={`Actions for ${department.name}`}>
                  <MoreHorizontal aria-hidden="true" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuItem onSelect={() => setEditing({ mode: 'edit', department })}>Edit</DropdownMenuItem>
                <DropdownMenuItem className="text-destructive focus:text-destructive" onSelect={() => setDeleting(department)}>
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          ),
      },
    ],
    [],
  );

  return (
    <>
      <PageHeader
        title="Departments"
        description="The organisational units employees belong to."
        actions={
          canManage && (
            <Button onClick={() => setEditing({ mode: 'create' })}>
              <Plus aria-hidden="true" />
              Add department
            </Button>
          )
        }
      />

      {departments.isError ? (
        <ErrorState error={departments.error} onRetry={() => departments.refetch()} />
      ) : (
        <DataTable
          caption="Departments"
          columns={columns}
          rows={departments.data?.departments}
          getRowId={(department) => department.id}
          isLoading={departments.isPending}
          empty={<EmptyState icon={Building2} title="No departments yet" description={canManage ? 'Add the first department to start organising employees.' : undefined} />}
        />
      )}

      <Drawer
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        title={editing?.mode === 'edit' ? `Edit ${editing.department.name}` : 'New department'}
        footer={
          <>
            <Button variant="outline" onClick={() => setEditing(null)}>
              Cancel
            </Button>
            <Button type="submit" form={formId} loading={save.isPending}>
              {editing?.mode === 'edit' ? 'Save changes' : 'Create department'}
            </Button>
          </>
        }
      >
        {editing && (
          <DepartmentForm
            key={editing.mode === 'edit' ? editing.department.id : 'new'}
            id={formId}
            department={editing.mode === 'edit' ? editing.department : undefined}
            departments={departments.data?.departments ?? []}
            onSubmit={onSubmit}
          />
        )}
      </Drawer>

      <ConfirmDialog
        open={deleting !== null}
        onOpenChange={(open) => !open && setDeleting(null)}
        title={`Delete ${deleting?.name ?? 'department'}?`}
        description="Departments that still have sub-departments or employees cannot be deleted."
        confirmLabel="Delete department"
        destructive
        pending={remove.isPending}
        onConfirm={confirmDelete}
      />
    </>
  );
}
