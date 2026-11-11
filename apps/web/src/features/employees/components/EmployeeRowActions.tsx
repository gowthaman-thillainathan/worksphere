import { MoreHorizontal } from 'lucide-react';
import { Link } from 'react-router';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { paths } from '@/routes/paths';
import type { Employee } from '@/types/api';

export interface EmployeeActionHandlers {
  onDelete: (employee: Employee) => void;
  onRestore: (employee: Employee) => void;
}

/** Per-record actions, offered only when the API says this user may take them (meta.permissions). */
export function EmployeeRowActions({ employee, onDelete, onRestore }: EmployeeActionHandlers & { employee: Employee }) {
  const allowed = employee.meta.permissions;
  const deleted = employee.deleted_at !== null;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" aria-label={`Actions for ${employee.display_name}`}>
          <MoreHorizontal aria-hidden="true" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {deleted ? (
          allowed.includes('delete') && <DropdownMenuItem onSelect={() => onRestore(employee)}>Restore</DropdownMenuItem>
        ) : (
          <>
            <DropdownMenuItem asChild>
              <Link to={paths.employee(employee.id)}>View profile</Link>
            </DropdownMenuItem>
            {allowed.includes('update') && (
              <DropdownMenuItem asChild>
                <Link to={paths.editEmployee(employee.id)}>Edit</Link>
              </DropdownMenuItem>
            )}
            {allowed.includes('delete') && (
              <DropdownMenuItem className="text-destructive focus:text-destructive" onSelect={() => onDelete(employee)}>
                Delete
              </DropdownMenuItem>
            )}
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
