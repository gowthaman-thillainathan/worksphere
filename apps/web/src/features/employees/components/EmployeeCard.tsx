import { memo } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Card } from '@/components/ui/card';
import { cn } from '@/shared/lib/utils';
import type { Employee } from '@/types/api';
import { EmployeeAvatar } from './EmployeeAvatar';
import { EmployeeNameLink } from './EmployeeNameLink';
import { EmployeeRowActions, type EmployeeActionHandlers } from './EmployeeRowActions';
import { EmployeeStatusBadge } from './EmployeeStatusBadge';

interface EmployeeCardProps extends EmployeeActionHandlers {
  employee: Employee;
  selected?: boolean;
  onSelectedChange?: (employee: Employee, selected: boolean) => void;
}

/** One employee as a card: the directory layout on phones. */
export const EmployeeCard = memo(function EmployeeCard({ employee, selected = false, onSelectedChange, onDelete, onRestore }: EmployeeCardProps) {
  return (
    <Card className={cn('flex items-start gap-3 p-4', selected && 'border-primary bg-primary/5')}>
      {onSelectedChange && (
        <Checkbox className="mt-3" aria-label={`Select ${employee.display_name}`} checked={selected} onCheckedChange={(checked) => onSelectedChange(employee, checked === true)} />
      )}
      <EmployeeAvatar employee={employee} size="md" />
      <div className="min-w-0 flex-1 space-y-1">
        <EmployeeNameLink employee={employee} />
        <p className="truncate text-sm text-muted-foreground">{employee.job_title}</p>
        <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
          <EmployeeStatusBadge status={employee.status} deleted={employee.deleted_at !== null} />
          <span className="font-mono">{employee.employee_number}</span>
          {employee.department_name && <span>{employee.department_name}</span>}
        </div>
      </div>
      <EmployeeRowActions employee={employee} onDelete={onDelete} onRestore={onRestore} />
    </Card>
  );
});
