import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { useCallback, useRef, useState, type ReactNode } from 'react';
import { FormProvider, useForm, type FieldPath } from 'react-hook-form';
import { Link } from 'react-router';
import { Stepper } from '@/components/Stepper';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader } from '@/components/ui/card';
import { useDepartments } from '@/features/departments/department-queries';
import { useRoles } from '@/features/roles/role-queries';
import { useFormServerErrors } from '@/hooks/use-form-server-errors';
import { ApiError } from '@/services/api/api-error';
import { useAuth } from '@/shared/auth/use-auth';
import { Permission } from '@/types/permissions';
import { emptyWizardForm, employeeWizardSchema, stepOfField, WIZARD_STEPS, type EmployeeFormValues, type EmployeeWizardValues } from '../lib/employee-form-schema';
import { formatFormValue } from '../lib/form-values';
import { FIELD_LABELS } from '../lib/labels';
import { WIZARD_ERROR_ALIASES, WIZARD_ERROR_CODES, WIZARD_ERROR_FIELDS, wizardErrorField } from '../lib/server-error-fields';
import { AccountAccessFields, EmploymentFields, PersonalFields, StatusFields } from './EmployeeFormSections';

const REVIEW_STEP = WIZARD_STEPS.length - 1;

interface EmployeeCreateWizardProps {
  /** Throws on failure; errors are shown on their fields, on the step that owns them. */
  onSubmit: (values: EmployeeWizardValues) => Promise<void>;
  cancelTo: string;
}

/**
 * Four steps: personal information, employment, account, review. Each step
 * validates only its own fields before moving on; the API's answer is mapped
 * back onto the field and the wizard jumps to its step.
 */
export function EmployeeCreateWizard({ onSubmit, cancelTo }: EmployeeCreateWizardProps) {
  const { can } = useAuth();
  const includeSensitive = can(Permission.EmployeeUpdateSensitive);
  const canManageAccounts = can(Permission.UserManage) && can(Permission.AccessManage);
  const [step, setStep] = useState(0);
  const headingRef = useRef<HTMLHeadingElement>(null);
  const form = useForm<EmployeeWizardValues>({ resolver: zodResolver(employeeWizardSchema), defaultValues: emptyWizardForm(), mode: 'onTouched' });

  const goTo = useCallback((next: number) => {
    setStep(next);
    // Move focus to the new step's heading so keyboard and screen reader users follow along.
    requestAnimationFrame(() => headingRef.current?.focus());
  }, []);

  const handleServerError = useFormServerErrors(form.setError, { fields: WIZARD_ERROR_FIELDS, aliases: WIZARD_ERROR_ALIASES, codeFields: WIZARD_ERROR_CODES });

  const next = async () => {
    const fields = WIZARD_STEPS[step]?.fields as readonly FieldPath<EmployeeWizardValues>[] | undefined;

    if (await form.trigger(fields, { shouldFocus: true })) goTo(step + 1);
  };

  const submit = form.handleSubmit(async (values) => {
    try {
      await onSubmit(values);
    } catch (error) {
      const apiError = ApiError.from(error);
      const field = wizardErrorField(apiError.fieldErrors, apiError.code);
      const target = field ? stepOfField(field) : -1;

      // Show the step first, so the field is visible when the error focuses it.
      if (target >= 0) setStep(target);
      requestAnimationFrame(() => handleServerError(error));
    }
  });

  const current = WIZARD_STEPS[step] ?? WIZARD_STEPS[0];

  return (
    <FormProvider {...form}>
      <div className="grid gap-6">
        <Stepper steps={WIZARD_STEPS} current={step} onSelect={goTo} />

        <form
          onSubmit={(event) => {
            if (step < REVIEW_STEP) {
              event.preventDefault();
              void next();

              return;
            }

            void submit(event);
          }}
          noValidate
        >
          <Card>
            <CardHeader>
              <h2 ref={headingRef} tabIndex={-1} className="font-semibold leading-none tracking-tight outline-none">
                {current.title}
              </h2>
              <CardDescription>
                Step {step + 1} of {WIZARD_STEPS.length}: {current.description}
              </CardDescription>
            </CardHeader>
            <CardContent>
              {/* Fields of other steps stay registered (and keep their values) while hidden. */}
              <div hidden={step !== 0}>
                <PersonalFields includeSensitive={includeSensitive} />
              </div>
              <div hidden={step !== 1}>
                <EmploymentFields />
              </div>
              <div hidden={step !== 2} className="grid gap-6">
                <StatusFields />
                {canManageAccounts ? (
                  <AccountAccessFields />
                ) : (
                  <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    Sign-in access is given by an administrator who manages users and roles. They can do it later from the employee&apos;s profile.
                  </p>
                )}
              </div>
              {step === REVIEW_STEP && <WizardReview values={form.getValues()} onEdit={goTo} includeSensitive={includeSensitive} />}
            </CardContent>
          </Card>

          <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <Button asChild variant="ghost">
              <Link to={cancelTo}>Cancel</Link>
            </Button>
            <div className="flex flex-col-reverse gap-2 sm:flex-row">
              {step > 0 && (
                <Button type="button" variant="outline" onClick={() => goTo(step - 1)}>
                  <ArrowLeft aria-hidden="true" />
                  Back
                </Button>
              )}
              {step < REVIEW_STEP ? (
                <Button type="submit">
                  Next
                  <ArrowRight aria-hidden="true" />
                </Button>
              ) : (
                <Button type="submit" loading={form.formState.isSubmitting}>
                  Create employee
                </Button>
              )}
            </div>
          </div>
        </form>
      </div>
    </FormProvider>
  );
}

const REVIEW_SECTIONS: { step: number; fields: (keyof EmployeeFormValues)[]; sensitive?: (keyof EmployeeFormValues)[] }[] = [
  { step: 0, fields: ['first_name', 'last_name', 'preferred_name', 'work_email'], sensitive: ['phone', 'personal_email', 'date_of_birth'] },
  { step: 1, fields: ['job_title', 'department_id', 'manager', 'employment_type', 'hire_date'] },
  { step: 2, fields: ['status', 'termination_date'] },
];

function WizardReview({ values, onEdit, includeSensitive }: { values: EmployeeWizardValues; onEdit: (step: number) => void; includeSensitive: boolean }) {
  const { can } = useAuth();
  const departments = useDepartments(can(Permission.OrganisationView));
  const roles = useRoles(values.grant_access);
  const departmentName = (id: string) => departments.data?.departments.find((department) => department.id === id)?.name;

  return (
    <div className="grid gap-6">
      {REVIEW_SECTIONS.map(({ step, fields, sensitive = [] }) => (
        <ReviewSection key={step} title={WIZARD_STEPS[step]?.title ?? ''} onEdit={() => onEdit(step)}>
          {[...fields, ...(includeSensitive ? sensitive : [])].map((field) => (
            <ReviewItem key={field} label={FIELD_LABELS[field] ?? field} value={formatFormValue(field, values[field], departmentName)} />
          ))}
          {step === 2 && (
            <ReviewItem
              label="Sign-in access"
              value={values.grant_access ? `Yes, as ${roles.data?.find((role) => role.key === values.account_role)?.name ?? values.account_role}` : 'No'}
            />
          )}
        </ReviewSection>
      ))}
      <p className="text-sm text-muted-foreground">The employee ID is assigned when you create the employee.</p>
    </div>
  );
}

function ReviewSection({ title, onEdit, children }: { title: string; onEdit: () => void; children: ReactNode }) {
  return (
    <section aria-label={title} className="rounded-lg border">
      <div className="flex items-center justify-between border-b px-4 py-2">
        <h3 className="text-sm font-semibold">{title}</h3>
        <Button type="button" variant="link" size="sm" className="h-auto p-0" onClick={onEdit} aria-label={`Edit ${title}`}>
          Edit
        </Button>
      </div>
      <dl className="grid gap-3 p-4 sm:grid-cols-2">{children}</dl>
    </section>
  );
}

function ReviewItem({ label, value }: { label: string; value: string }) {
  return (
    <div className="min-w-0">
      <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</dt>
      <dd className="break-words text-sm">{value}</dd>
    </div>
  );
}
