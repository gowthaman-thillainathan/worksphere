import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { useFormServerErrors } from '@/hooks/use-form-server-errors';
import { ApiError } from '@/services/api/api-error';
import { describeError } from '@/shared/lib/error-messages';
import type { Employee } from '@/types/api';
import { useBulkDeleteEmployees, useDeleteEmployee } from '../api/employee-mutations';
import { announceBulkOutcome } from '../lib/bulk-outcome';
import { DELETION_REASON, deletionSchema, type DeletionValues } from '../lib/deletion-schema';

const FIELDS = ['reason'] as const;

interface EmployeeDeleteDialogProps {
  /** One employee, several (bulk), or none when closed. */
  employees: Employee[];
  onOpenChange: (open: boolean) => void;
  onDeleted?: () => void;
}

/**
 * Soft delete with a required reason, kept with the record and in the audit
 * trail. Deleted employees can be restored from the "Deleted records" view.
 */
export function EmployeeDeleteDialog({ employees, onOpenChange, onDeleted }: EmployeeDeleteDialogProps) {
  const single = useDeleteEmployee();
  const bulk = useBulkDeleteEmployees();
  const open = employees.length > 0;
  const [first] = employees;
  const subject = employees.length === 1 && first ? first.display_name : `${employees.length} employees`;

  const { register, handleSubmit, reset, setError, formState } = useForm<DeletionValues>({ resolver: zodResolver(deletionSchema), defaultValues: { reason: '' } });
  const handleServerError = useFormServerErrors(setError, { fields: FIELDS });

  useEffect(() => {
    if (open) reset({ reason: '' });
  }, [open, reset]);

  const submit = handleSubmit(async ({ reason }) => {
    try {
      if (employees.length === 1 && first) {
        await single.mutateAsync({ employee: first, reason });
        toast.success(`${first.display_name} was deleted.`, { description: 'You can restore them from the deleted records.' });
      } else {
        announceBulkOutcome(await bulk.mutateAsync({ employees, reason }), 'deleted');
      }

      onOpenChange(false);
      onDeleted?.();
    } catch (error) {
      if (ApiError.from(error).hasFieldErrors) {
        handleServerError(error);

        return;
      }

      const { title, description } = describeError(error);
      toast.error(title, { description });
      onOpenChange(false);
    }
  });

  return (
    <Modal
      open={open}
      onOpenChange={onOpenChange}
      title={`Delete ${subject}?`}
      description="They will leave the directory but keep their history and documents, and can be restored later."
      footer={
        <>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={formState.isSubmitting}>
            Cancel
          </Button>
          <Button variant="destructive" type="submit" form="delete-employee-form" loading={formState.isSubmitting}>
            {employees.length > 1 ? `Delete ${employees.length} employees` : 'Delete employee'}
          </Button>
        </>
      }
    >
      <form id="delete-employee-form" onSubmit={submit} noValidate>
        <FormField label="Reason" required error={formState.errors.reason?.message} hint={`Recorded in the audit trail. ${DELETION_REASON.min}–${DELETION_REASON.max} characters.`}>
          <Textarea rows={3} maxLength={DELETION_REASON.max} placeholder="e.g. Duplicate record created during onboarding" {...register('reason')} />
        </FormField>
      </form>
    </Modal>
  );
}
