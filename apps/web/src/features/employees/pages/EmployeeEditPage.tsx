import { useNavigate, useParams } from 'react-router';
import { toast } from 'sonner';
import { ErrorState } from '@/components/ErrorState';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PageHeader } from '@/components/PageHeader';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { ApiError } from '@/services/api/api-error';
import { useAuth } from '@/shared/auth/use-auth';
import type { Employee } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useEmployee, useUpdateEmployee } from '../api/employee-queries';
import { EmployeeForm } from '../components/EmployeeForm';
import { changedFields, employeeToForm, formToPayload, type EmployeeFormValues } from '../lib/employee-form-schema';

const HTTP_PRECONDITION_FAILED = 412;

export default function EmployeeEditPage() {
  const { employeeId = '' } = useParams();
  const employee = useEmployee(employeeId);
  useDocumentTitle(employee.data ? `Edit ${employee.data.display_name}` : 'Edit employee');

  if (employee.isPending) return <LoadingScreen />;
  if (employee.isError) return <ErrorState error={employee.error} onRetry={() => employee.refetch()} />;

  return <EditEmployee employee={employee.data} onReload={() => employee.refetch()} />;
}

function EditEmployee({ employee, onReload }: { employee: Employee; onReload: () => void }) {
  const navigate = useNavigate();
  const { can } = useAuth();
  const update = useUpdateEmployee(employee.id);
  const includeSensitive = can(Permission.EmployeeUpdateSensitive);
  const original = formToPayload(employeeToForm(employee), includeSensitive);

  const onSubmit = async (values: EmployeeFormValues) => {
    const changes = changedFields(original, formToPayload(values, includeSensitive));

    if (Object.keys(changes).length === 0) {
      void navigate(paths.employee(employee.id));

      return;
    }

    try {
      await update.mutateAsync({ version: employee.meta.version, changes });
      toast.success('Changes saved.');
      void navigate(paths.employee(employee.id));
    } catch (error) {
      if (ApiError.from(error).status === HTTP_PRECONDITION_FAILED) {
        toast.error('Someone else changed this employee', {
          description: 'Reload to get the latest version, then apply your changes again.',
          action: { label: 'Reload', onClick: onReload },
        });

        return;
      }

      throw error;
    }
  };

  return (
    <>
      <PageHeader title={`Edit ${employee.display_name}`} description={employee.employee_number} />
      <EmployeeForm
        key={employee.meta.version}
        defaultValues={employeeToForm(employee)}
        onSubmit={onSubmit}
        submitLabel="Save changes"
        cancelTo={paths.employee(employee.id)}
        employeeId={employee.id}
      />
    </>
  );
}
