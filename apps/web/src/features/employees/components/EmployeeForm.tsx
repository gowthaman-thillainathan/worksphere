import type { ReactNode } from 'react';
import { FormProvider, type UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useFormServerErrors } from '@/hooks/use-form-server-errors';
import type { EmployeeFormValues } from '../lib/employee-form-schema';
import { SERVER_ERROR_ALIASES, SERVER_ERROR_CODES, SERVER_ERROR_FIELDS } from '../lib/server-error-fields';
import { EmploymentFields, PersonalFields, StatusFields } from './EmployeeFormSections';

interface EmployeeFormProps {
  form: UseFormReturn<EmployeeFormValues>;
  /** Throws on failure; validation and business errors are shown on their fields. */
  onSubmit: (values: EmployeeFormValues) => Promise<void>;
  submitLabel: string;
  cancelTo: string;
  employeeNumber: string;
  employeeId: string;
  includeSensitive: boolean;
}

function Section({ title, description, children }: { title: string; description?: string; children: ReactNode }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent>{children}</CardContent>
    </Card>
  );
}

/** The edit form: every section on one page, sharing its fields with the create wizard. */
export function EmployeeForm({ form, onSubmit, submitLabel, cancelTo, employeeNumber, employeeId, includeSensitive }: EmployeeFormProps) {
  const handleServerError = useFormServerErrors(form.setError, {
    fields: SERVER_ERROR_FIELDS,
    aliases: SERVER_ERROR_ALIASES,
    codeFields: SERVER_ERROR_CODES,
  });

  const submit = form.handleSubmit(async (values) => {
    try {
      await onSubmit(values);
    } catch (error) {
      handleServerError(error);
    }
  });

  return (
    <FormProvider {...form}>
      <form onSubmit={submit} noValidate className="grid gap-6">
        <Section title="Personal information">
          <PersonalFields includeSensitive={includeSensitive} />
        </Section>
        <Section title="Employment">
          <EmploymentFields employeeNumber={employeeNumber} employeeId={employeeId} />
        </Section>
        <Section title="Status">
          <StatusFields />
        </Section>

        <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <Button asChild variant="outline">
            <Link to={cancelTo}>Cancel</Link>
          </Button>
          <Button type="submit" loading={form.formState.isSubmitting}>
            {submitLabel}
          </Button>
        </div>
      </form>
    </FormProvider>
  );
}
