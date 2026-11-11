import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { describeError } from '@/shared/lib/error-messages';
import type { Employee } from '@/types/api';
import { useDeleteEmployee } from '../api/employee-queries';

interface DeleteEmployeeDialogProps {
  employee: Employee | null;
  onOpenChange: (open: boolean) => void;
  onDeleted?: () => void;
}

/** Soft delete: the employee can be restored from the "Deleted only" view. */
export function DeleteEmployeeDialog({ employee, onOpenChange, onDeleted }: DeleteEmployeeDialogProps) {
  const remove = useDeleteEmployee();

  const confirm = () => {
    if (!employee) return;

    remove.mutate(
      { id: employee.id, version: employee.meta.version },
      {
        onSuccess: () => {
          toast.success(`${employee.display_name} was deleted.`, { description: 'You can restore them from the deleted employees view.' });
          onOpenChange(false);
          onDeleted?.();
        },
        onError: (error) => {
          const { title, description } = describeError(error);
          toast.error(title, { description });
          onOpenChange(false);
        },
      },
    );
  };

  return (
    <ConfirmDialog
      open={employee !== null}
      onOpenChange={onOpenChange}
      title={`Delete ${employee?.display_name ?? 'employee'}?`}
      description="They will disappear from the directory but keep their history, and can be restored later."
      confirmLabel="Delete employee"
      destructive
      pending={remove.isPending}
      onConfirm={confirm}
    />
  );
}
