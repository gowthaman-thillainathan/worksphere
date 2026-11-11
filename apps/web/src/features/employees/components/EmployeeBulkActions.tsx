import { Download, RotateCcw, Trash2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/shared/auth/use-auth';
import type { Employee } from '@/types/api';
import { Permission } from '@/types/permissions';
import { exportEmployees } from '../lib/employee-export';

interface EmployeeBulkActionsProps {
  selected: Employee[];
  onDelete: (employees: Employee[]) => void;
  onRestore: (employees: Employee[]) => void;
  onClear: () => void;
}

/**
 * Actions for the selected rows. Delete and restore apply only to the rows
 * where they make sense (active vs deleted records); the buttons say how many.
 */
export function EmployeeBulkActions({ selected, onDelete, onRestore, onClear }: EmployeeBulkActionsProps) {
  const { can } = useAuth();

  if (selected.length === 0) return null;

  const deletable = selected.filter((employee) => employee.deleted_at === null && employee.meta.permissions.includes('delete'));
  const restorable = selected.filter((employee) => employee.deleted_at !== null && employee.meta.permissions.includes('delete'));

  return (
    <div role="region" aria-label="Bulk actions" className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/40 px-3 py-2">
      <p className="mr-auto text-sm font-medium" aria-live="polite">
        {selected.length} selected
      </p>
      {can(Permission.EmployeeExport) && (
        <Button variant="outline" size="sm" onClick={() => exportEmployees(selected)}>
          <Download aria-hidden="true" />
          Export CSV
        </Button>
      )}
      {restorable.length > 0 && (
        <Button variant="outline" size="sm" onClick={() => onRestore(restorable)}>
          <RotateCcw aria-hidden="true" />
          Restore {restorable.length}
        </Button>
      )}
      {deletable.length > 0 && (
        <Button variant="outline" size="sm" className="text-destructive" onClick={() => onDelete(deletable)}>
          <Trash2 aria-hidden="true" />
          Delete {deletable.length}
        </Button>
      )}
      <Button variant="ghost" size="sm" onClick={onClear}>
        <X aria-hidden="true" />
        Clear selection
      </Button>
    </div>
  );
}
