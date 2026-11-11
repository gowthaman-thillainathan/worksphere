import { useNavigate } from 'react-router';
import { toast } from 'sonner';
import { PageHeader } from '@/components/PageHeader';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { useAuth } from '@/shared/auth/use-auth';
import { Permission } from '@/types/permissions';
import { useCreateEmployee } from '../api/employee-mutations';
import { EmployeeCreateWizard } from '../components/EmployeeCreateWizard';
import { wizardToPayload, type EmployeeWizardValues } from '../lib/employee-form-schema';

export default function EmployeeCreatePage() {
  useDocumentTitle('New employee');
  const navigate = useNavigate();
  const { can } = useAuth();
  const create = useCreateEmployee();

  const onSubmit = async (values: EmployeeWizardValues) => {
    const employee = await create.mutateAsync(wizardToPayload(values, can(Permission.EmployeeUpdateSensitive)));
    toast.success(`${employee.display_name} was added as ${employee.employee_number}.`, {
      description: employee.has_account ? `An invitation was sent to ${employee.work_email}.` : undefined,
    });
    void navigate(paths.employee(employee.id));
  };

  return (
    <>
      <PageHeader title="New employee" description="Add someone to the directory in four short steps." />
      <EmployeeCreateWizard onSubmit={onSubmit} cancelTo={paths.employees} />
    </>
  );
}
