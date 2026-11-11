import { useController, useFormContext, useWatch } from 'react-hook-form';
import { FormField } from '@/components/FormField';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { useDepartments } from '@/features/departments/department-queries';
import { useRoles } from '@/features/roles/role-queries';
import { useAuth } from '@/shared/auth/use-auth';
import { EmployeeStatus } from '@/types/api';
import { Permission } from '@/types/permissions';
import type { EmployeeFormValues, EmployeeWizardValues } from '../lib/employee-form-schema';
import { EMPLOYEE_STATUSES, EMPLOYMENT_TYPES, EMPLOYMENT_TYPE_LABELS, STATUS_LABELS } from '../lib/labels';
import { EmployeePicker } from './EmployeePicker';

/*
| Field groups shared by the edit form and the create wizard. Each reads the
| surrounding <FormProvider>, so the same fields, labels and rules are used
| in both places.
*/

export function PersonalFields({ includeSensitive }: { includeSensitive: boolean }) {
  const { register, formState } = useFormContext<EmployeeFormValues>();
  const { errors } = formState;

  return (
    <div className="grid gap-4 sm:grid-cols-2">
      <FormField label="First name" error={errors.first_name?.message} required>
        <Input autoComplete="off" {...register('first_name')} />
      </FormField>
      <FormField label="Last name" error={errors.last_name?.message} required>
        <Input autoComplete="off" {...register('last_name')} />
      </FormField>
      <FormField label="Preferred name" error={errors.preferred_name?.message} hint="Shown instead of the first name.">
        <Input autoComplete="off" {...register('preferred_name')} />
      </FormField>
      <FormField label="Work e-mail" error={errors.work_email?.message} required hint="Also used to sign in, if access is given.">
        <Input type="email" autoComplete="off" {...register('work_email')} />
      </FormField>
      {includeSensitive && (
        <>
          <FormField label="Phone" error={errors.phone?.message} hint="Personal data: encrypted and only visible to authorised people.">
            <Input type="tel" autoComplete="off" {...register('phone')} />
          </FormField>
          <FormField label="Personal e-mail" error={errors.personal_email?.message}>
            <Input type="email" autoComplete="off" {...register('personal_email')} />
          </FormField>
          <FormField label="Date of birth" error={errors.date_of_birth?.message}>
            <Input type="date" {...register('date_of_birth')} />
          </FormField>
        </>
      )}
    </div>
  );
}

interface EmploymentFieldsProps {
  /** The existing employee number, or none for a new employee (it is generated). */
  employeeNumber?: string;
  /** The employee being edited, left out of the manager search. */
  employeeId?: string;
}

export function EmploymentFields({ employeeNumber, employeeId }: EmploymentFieldsProps) {
  const { register, control, formState } = useFormContext<EmployeeFormValues>();
  const { field: managerField } = useController({ control, name: 'manager' });
  // Controlled: the options arrive after the form is rendered, and an uncontrolled
  // <select> would silently fall back to "No department".
  const { field: departmentField } = useController({ control, name: 'department_id' });
  const { can } = useAuth();
  const canSeeDepartments = can(Permission.OrganisationView);
  const departments = useDepartments(canSeeDepartments);
  const { errors } = formState;

  return (
    <div className="grid gap-4 sm:grid-cols-2">
      <FormField label="Employee ID" hint={employeeNumber ? 'Assigned when the employee was created.' : 'Assigned automatically when you create the employee.'}>
        <Input value={employeeNumber ?? 'Generated on save'} readOnly className="bg-muted font-mono" />
      </FormField>
      <FormField label="Designation" error={errors.job_title?.message} required>
        <Input {...register('job_title')} />
      </FormField>
      {canSeeDepartments && (
        <FormField label="Department" error={errors.department_id?.message}>
          <Select {...departmentField} disabled={departments.isPending}>
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
      <FormField label="Employment type" error={errors.employment_type?.message} required>
        <Select {...register('employment_type')}>
          {EMPLOYMENT_TYPES.map((type) => (
            <option key={type} value={type}>
              {EMPLOYMENT_TYPE_LABELS[type]}
            </option>
          ))}
        </Select>
      </FormField>
      <FormField label="Join date" error={errors.hire_date?.message} required>
        <Input type="date" {...register('hire_date')} />
      </FormField>
    </div>
  );
}

export function StatusFields() {
  const { register, control, formState } = useFormContext<EmployeeFormValues>();
  const status = useWatch({ control, name: 'status' });
  const { errors } = formState;

  return (
    <div className="grid gap-4 sm:grid-cols-2">
      <FormField label="Employment status" error={errors.status?.message} required>
        <Select {...register('status')}>
          {EMPLOYEE_STATUSES.map((value) => (
            <option key={value} value={value}>
              {STATUS_LABELS[value]}
            </option>
          ))}
        </Select>
      </FormField>
      <FormField
        label="Termination date"
        error={errors.termination_date?.message}
        required={status === EmployeeStatus.terminated}
        hint="Required when the status is Terminated."
      >
        <Input type="date" {...register('termination_date')} />
      </FormField>
    </div>
  );
}

/** Wizard only: optional sign-in account with one role. */
export function AccountAccessFields() {
  const { control, formState, setValue } = useFormContext<EmployeeWizardValues>();
  const grantAccess = useWatch({ control, name: 'grant_access' });
  const { field: roleField } = useController({ control, name: 'account_role' });
  const roles = useRoles(grantAccess);

  return (
    <div className="grid gap-4 rounded-lg border p-4">
      <div className="flex items-start gap-3">
        <Checkbox
          id="grant-access"
          checked={grantAccess}
          onCheckedChange={(checked) => setValue('grant_access', checked === true, { shouldDirty: true, shouldValidate: formState.isSubmitted })}
        />
        <div className="grid gap-1">
          <Label htmlFor="grant-access">Give sign-in access</Label>
          <p className="text-xs text-muted-foreground">An account is created on the work e-mail address and the employee is invited to set a password.</p>
        </div>
      </div>
      {grantAccess && (
        <FormField label="Role" error={formState.errors.account_role?.message} required hint="What the employee may see and do in Meridian.">
          <Select {...roleField} disabled={roles.isPending}>
            <option value="">Choose a role</option>
            {roles.data?.map((role) => (
              <option key={role.key} value={role.key}>
                {role.name}
              </option>
            ))}
          </Select>
        </FormField>
      )}
    </div>
  );
}
