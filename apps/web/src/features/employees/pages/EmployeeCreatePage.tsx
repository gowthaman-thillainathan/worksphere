import { useNavigate } from 'react-router';
import { toast } from 'sonner';
import { PageHeader } from '@/components/PageHeader';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { useAuth } from '@/shared/auth/use-auth';
import { Permission } from '@/types/permissions';
import { useCreateEmployee } from '../api/employee-queries';
import { EmployeeForm } from '../components/EmployeeForm';
import { emptyEmployeeForm, formToPayload, type EmployeeFormValues } from '../lib/employee-form-schema';

export default function EmployeeCreatePage() {
  useDocumentTitle('New employee');
  const navigate = useNavigate();
  const { can } = useAuth();
  const create = useCreateEmployee();

  const onSubmit = async (values: EmployeeFormValues) => {
    const employee = await create.mutateAsync(formToPayload(values, can(Permission.EmployeeUpdateSensitive)));
    toast.success(`${employee.display_name} was added as ${employee.employee_number}.`);
    void navigate(paths.employee(employee.id));
  };

  return (
    <>
      <PageHeader title="New employee" description="The employee number is assigned automatically." />
      <EmployeeForm defaultValues={emptyEmployeeForm()} onSubmit={onSubmit} submitLabel="Create employee" cancelTo={paths.employees} />
    </>
  );
}
