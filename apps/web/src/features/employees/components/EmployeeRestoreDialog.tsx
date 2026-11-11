import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { describeError } from '@/shared/lib/error-messages';
import type { Employee } from '@/types/api';
import { useBulkRestoreEmployees, useRestoreEmployee } from '../api/employee-mutations';
import { announceBulkOutcome } from '../lib/bulk-outcome';

interface EmployeeRestoreDialogProps {
  /** One employee, several (bulk), or none when closed. */
  employees: Employee[];
  onOpenChange: (open: boolean) => void;
}

/** Brings deleted employees back into the directory; lists and counts refresh automatically. */
export function EmployeeRestoreDialog({ employees, onOpenChange }: EmployeeRestoreDialogProps) {
  const single = useRestoreEmployee();
  const bulk = useBulkRestoreEmployees();
  const [first] = employees;
  const subject = employees.length === 1 && first ? first.display_name : `${employees.length} employees`;

  const confirm = () => {
    onOpenChange(false);

    if (employees.length === 1 && first) {
      single.mutate(first, {
        onSuccess: () => toast.success(`${first.display_name} was restored.`),
        onError: (error) => toast.error(describeError(error).title, { description: describeError(error).description }),
      });

      return;
    }

    bulk.mutate(employees, { onSuccess: (outcome) => announceBulkOutcome(outcome, 'restored') });
  };

  return (
    <ConfirmDialog
      open={employees.length > 0}
      onOpenChange={onOpenChange}
      title={`Restore ${subject}?`}
      description={
        employees.length === 1 && first?.deletion_reason ? (
          <>
            Deleted because: <q>{first.deletion_reason}</q>. They will reappear in the directory with their history.
          </>
        ) : (
          'They will reappear in the directory with their history.'
        )
      }
      confirmLabel={employees.length > 1 ? `Restore ${employees.length} employees` : 'Restore employee'}
      pending={single.isPending || bulk.isPending}
      onConfirm={confirm}
    />
  );
}
