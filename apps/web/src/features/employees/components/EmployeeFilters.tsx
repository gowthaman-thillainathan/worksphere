import { Filter } from 'lucide-react';
import { useId } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { useDepartments } from '@/features/departments/department-queries';
import type { EmployeeListQuery } from '@/services/employee-service';
import { useAuth } from '@/shared/auth/use-auth';
import type { EmployeeStatus, EmploymentType } from '@/types/api';
import { Permission } from '@/types/permissions';
import { EMPLOYEE_STATUSES, EMPLOYMENT_TYPES, EMPLOYMENT_TYPE_LABELS, STATUS_LABELS } from '../lib/labels';

interface EmployeeFiltersProps {
  query: EmployeeListQuery;
  onChange: (changes: Partial<EmployeeListQuery>) => void;
}

function toggle<T>(values: T[] | undefined, value: T): T[] {
  const current = values ?? [];

  return current.includes(value) ? current.filter((item) => item !== value) : [...current, value];
}

export function EmployeeFilters({ query, onChange }: EmployeeFiltersProps) {
  const typeId = useId();
  const departmentId = useId();
  const deletedId = useId();
  const { can } = useAuth();
  const canSeeDepartments = can(Permission.OrganisationView);
  const departments = useDepartments(canSeeDepartments);
  const statusCount = query.statuses?.length ?? 0;

  return (
    <div className="flex flex-wrap items-center gap-3">
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="outline" size="sm">
            <Filter aria-hidden="true" />
            Status{statusCount > 0 && ` (${statusCount})`}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start">
          <DropdownMenuLabel>Show statuses</DropdownMenuLabel>
          <DropdownMenuSeparator />
          {EMPLOYEE_STATUSES.map((status: EmployeeStatus) => (
            <DropdownMenuCheckboxItem
              key={status}
              checked={query.statuses?.includes(status) ?? false}
              onCheckedChange={() => onChange({ statuses: toggle(query.statuses, status) })}
              onSelect={(event) => event.preventDefault()}
            >
              {STATUS_LABELS[status]}
            </DropdownMenuCheckboxItem>
          ))}
        </DropdownMenuContent>
      </DropdownMenu>

      <div className="flex items-center gap-2">
        <Label htmlFor={typeId} className="sr-only">
          Employment type
        </Label>
        <Select
          id={typeId}
          className="h-8 w-48"
          value={query.employmentTypes?.[0] ?? ''}
          onChange={(event) => onChange({ employmentTypes: event.target.value ? [event.target.value as EmploymentType] : [] })}
        >
          <option value="">All employment types</option>
          {EMPLOYMENT_TYPES.map((type) => (
            <option key={type} value={type}>
              {EMPLOYMENT_TYPE_LABELS[type]}
            </option>
          ))}
        </Select>
      </div>

      {canSeeDepartments && (
        <div className="flex items-center gap-2">
          <Label htmlFor={departmentId} className="sr-only">
            Department
          </Label>
          <Select
            id={departmentId}
            className="h-8 w-44"
            value={query.departmentId ?? ''}
            onChange={(event) => onChange({ departmentId: event.target.value || undefined })}
            disabled={departments.isPending}
          >
            <option value="">All departments</option>
            {departments.data?.departments.map((department) => (
              <option key={department.id} value={department.id}>
                {department.name}
              </option>
            ))}
          </Select>
        </div>
      )}

      {can(Permission.EmployeeDelete) && (
        <div className="flex items-center gap-2">
          <Checkbox id={deletedId} checked={query.trashed === 'only'} onCheckedChange={(checked) => onChange({ trashed: checked === true ? 'only' : undefined })} />
          <Label htmlFor={deletedId} className="font-normal">
            Deleted only
          </Label>
        </div>
      )}
    </div>
  );
}
