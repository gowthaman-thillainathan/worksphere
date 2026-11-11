import { zodResolver } from '@hookform/resolvers/zod';
import { useCallback, useState } from 'react';
import { useForm } from 'react-hook-form';
import { useNavigate, useParams } from 'react-router';
import { toast } from 'sonner';
import { ErrorState } from '@/components/ErrorState';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PageHeader } from '@/components/PageHeader';
import { useDepartments } from '@/features/departments/department-queries';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { ApiError } from '@/services/api/api-error';
import { useAuth } from '@/shared/auth/use-auth';
import type { Employee } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useEmployee, useFetchLatestEmployee } from '../api/employee-queries';
import { useUpdateEmployee } from '../api/employee-mutations';
import { EmployeeConflictDialog } from '../components/EmployeeConflictDialog';
import { EmployeeForm } from '../components/EmployeeForm';
import { rebase, theirChanges, yourChanges, type FieldDifference } from '../lib/edit-conflict';
import { changedFields, employeeFormSchema, employeeToForm, formToPayload, type EmployeeFormValues } from '../lib/employee-form-schema';

const HTTP_PRECONDITION_FAILED = 412;

interface Conflict {
  latest: Employee;
  differences: FieldDifference[];
}

export default function EmployeeEditPage() {
  const { employeeId = '' } = useParams();
  const employee = useEmployee(employeeId);
  useDocumentTitle(employee.data ? `Edit ${employee.data.display_name}` : 'Edit employee');

  if (employee.isPending) return <LoadingScreen />;
  if (employee.isError) return <ErrorState error={employee.error} onRetry={() => employee.refetch()} />;

  // Keyed by id only: the form keeps its edits when the cached record refreshes.
  return <EditEmployee key={employee.data.id} initial={employee.data} />;
}

/**
 * Edits the version the user opened (`base`). Saving sends only the changed
 * fields with If-Match; if someone saved in between (412), the conflict
 * dialog shows what changed and never overwrites silently.
 */
function EditEmployee({ initial }: { initial: Employee }) {
  const navigate = useNavigate();
  const { can } = useAuth();
  const [base, setBase] = useState(initial);
  const [conflict, setConflict] = useState<Conflict | null>(null);
  const update = useUpdateEmployee(base.id);
  const fetchLatest = useFetchLatestEmployee();
  const departments = useDepartments(can(Permission.OrganisationView));
  const includeSensitive = base.meta.permissions.includes('update_sensitive');
  const form = useForm<EmployeeFormValues>({ resolver: zodResolver(employeeFormSchema), defaultValues: employeeToForm(base) });

  const departmentName = useCallback((id: string) => departments.data?.departments.find((department) => department.id === id)?.name, [departments.data]);

  const onSubmit = async (values: EmployeeFormValues) => {
    const changes = changedFields(formToPayload(employeeToForm(base), includeSensitive), formToPayload(values, includeSensitive));

    if (Object.keys(changes).length === 0) {
      void navigate(paths.employee(base.id));

      return;
    }

    try {
      await update.mutateAsync({ version: base.meta.version, changes });
      toast.success('Changes saved.');
      void navigate(paths.employee(base.id));
    } catch (error) {
      if (ApiError.from(error).status !== HTTP_PRECONDITION_FAILED) throw error;

      const latest = await fetchLatest(base.id);
      setConflict({ latest, differences: theirChanges(employeeToForm(base), employeeToForm(latest), values) });
    }
  };

  /** Continue from the latest version, optionally re-applying this user's edits as unsaved changes. */
  const continueFromLatest = (keepMine: boolean) => {
    if (!conflict) return;

    const mine = form.getValues();
    const edited = yourChanges(employeeToForm(base), mine);
    const merged = rebase(employeeToForm(base), employeeToForm(conflict.latest), mine);

    setBase(conflict.latest);
    form.reset(employeeToForm(conflict.latest));

    if (keepMine) {
      edited.forEach((field) => form.setValue(field, merged[field], { shouldDirty: true }));
      toast.info('Your changes were applied to the latest version.', { description: 'Review them and save again.' });
    } else {
      toast.info('Showing the latest version.');
    }

    setConflict(null);
  };

  return (
    <>
      <PageHeader title={`Edit ${base.display_name}`} description={`${base.employee_number} · version ${base.meta.version}`} />
      <EmployeeForm
        form={form}
        onSubmit={onSubmit}
        submitLabel="Save changes"
        cancelTo={paths.employee(base.id)}
        employeeNumber={base.employee_number}
        employeeId={base.id}
        includeSensitive={includeSensitive}
      />
      <EmployeeConflictDialog
        open={conflict !== null}
        differences={conflict?.differences ?? []}
        departmentName={departmentName}
        onDiscardMine={() => continueFromLatest(false)}
        onKeepMine={() => continueFromLatest(true)}
        onCancel={() => setConflict(null)}
      />
    </>
  );
}
