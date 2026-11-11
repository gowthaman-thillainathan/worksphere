import { zodResolver } from '@hookform/resolvers/zod';
import { useController, useForm } from 'react-hook-form';
import { Link } from 'react-router';
import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { useDepartments } from '@/features/departments/department-queries';
import { useFormServerErrors } from '@/hooks/use-form-server-errors';
import { useAuth } from '@/shared/auth/use-auth';
import { Permission } from '@/types/permissions';
import { EMPLOYEE_STATUSES, EMPLOYMENT_TYPES, EMPLOYMENT_TYPE_LABELS, STATUS_LABELS } from '../lib/labels';
import { EMPLOYEE_FORM_FIELDS, employeeFormSchema, type EmployeeFormValues } from '../lib/employee-form-schema';
import { EmployeePicker } from './EmployeePicker';

const FIELDS = [...EMPLOYEE_FORM_FIELDS, 'manager'] as const;
const ALIASES = { manager_id: 'manager' } as const;
const CODE_FIELDS = {
  work_email_in_use: 'work_email',
  invalid_manager: 'manager',
  invalid_department: 'department_id',
  invalid_employment_dates: 'termination_date',
} as const;

interface EmployeeFormProps {
  defaultValues: EmployeeFormValues;
  /** Throws an ApiError on failure; field errors are shown inline. */
  onSubmit: (values: EmployeeFormValues) => Promise<void>;
  submitLabel: string;
  cancelTo: string;
  /** The employee being edited, excluded from the manager search. */
  employeeId?: string;
}

export function EmployeeForm({ defaultValues, onSubmit, submitLabel, cancelTo, employeeId }: EmployeeFormProps) {
  const { can } = useAuth();
  const canEditSensitive = can(Permission.EmployeeUpdateSensitive);
  const canSeeDepartments = can(Permission.OrganisationView);
  const departments = useDepartments(canSeeDepartments);

  const { register, control, handleSubmit, setError, formState } = useForm<EmployeeFormValues>({
    resolver: zodResolver(employeeFormSchema),
    defaultValues,
  });
  const { field: managerField } = useController({ control, name: 'manager' });
  const handleServerError = useFormServerErrors(setError, { fields: FIELDS, aliases: ALIASES, codeFields: CODE_FIELDS });
  const { errors } = formState;

  const submit = handleSubmit(async (values) => {
    try {
      await onSubmit(values);
    } catch (error) {
      handleServerError(error);
    }
  });

  return (
    <form onSubmit={submit} noValidate className="grid gap-6">
      <Card>
        <CardHeader>
          <CardTitle>Personal details</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-3">
          <FormField label="First name" error={errors.first_name?.message} required>
            <Input autoComplete="off" {...register('first_name')} />
          </FormField>
          <FormField label="Last name" error={errors.last_name?.message} required>
            <Input autoComplete="off" {...register('last_name')} />
          </FormField>
          <FormField label="Preferred name" error={errors.preferred_name?.message} hint="Shown instead of the first name.">
            <Input autoComplete="off" {...register('preferred_name')} />
          </FormField>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Employment</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <FormField label="Work e-mail" error={errors.work_email?.message} required>
            <Input type="email" autoComplete="off" {...register('work_email')} />
          </FormField>
          <FormField label="Job title" error={errors.job_title?.message} required>
            <Input {...register('job_title')} />
          </FormField>
          <FormField label="Employment type" error={errors.employment_type?.message} required>
            <Select {...register('employment_type')}>
              {EMPLOYMENT_TYPES.map((type) => (
                <option key={type} value={type}>
                  {EMPLOYMENT_TYPE_LABELS[type]}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Status" error={errors.status?.message} required>
            <Select {...register('status')}>
              {EMPLOYEE_STATUSES.map((status) => (
                <option key={status} value={status}>
                  {STATUS_LABELS[status]}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Hire date" error={errors.hire_date?.message} required>
            <Input type="date" {...register('hire_date')} />
          </FormField>
          <FormField label="Termination date" error={errors.termination_date?.message} hint="Required when the status is Terminated.">
            <Input type="date" {...register('termination_date')} />
          </FormField>
          {canSeeDepartments && (
            <FormField label="Department" error={errors.department_id?.message}>
              <Select {...register('department_id')} disabled={departments.isPending}>
                <option value="">No department</option>
                {departments.data?.departments.map((department) => (
                  <option key={department.id} value={department.id}>
                    {department.name}
                  </option>
                ))}
              </Select>
            </FormField>
          )}
          <FormField label="Manager" error={errors.manager?.message}>
            <EmployeePicker value={managerField.value} onChange={managerField.onChange} onBlur={managerField.onBlur} excludeId={employeeId} />
          </FormField>
        </CardContent>
      </Card>

      {canEditSensitive && (
        <Card>
          <CardHeader>
            <CardTitle>Personal data</CardTitle>
            <CardDescription>Encrypted at rest and only visible to people allowed to see personal data.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-3">
            <FormField label="Personal e-mail" error={errors.personal_email?.message}>
              <Input type="email" autoComplete="off" {...register('personal_email')} />
            </FormField>
            <FormField label="Phone" error={errors.phone?.message}>
              <Input type="tel" autoComplete="off" {...register('phone')} />
            </FormField>
            <FormField label="Date of birth" error={errors.date_of_birth?.message}>
              <Input type="date" {...register('date_of_birth')} />
            </FormField>
          </CardContent>
        </Card>
      )}

      <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <Button asChild variant="outline">
          <Link to={cancelTo}>Cancel</Link>
        </Button>
        <Button type="submit" loading={formState.isSubmitting}>
          {submitLabel}
        </Button>
      </div>
    </form>
  );
}
