import { SlidersHorizontal, X } from 'lucide-react';
import { useId, useState, type ReactNode } from 'react';
import { FormField } from '@/components/FormField';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Drawer } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { useDepartments } from '@/features/departments/department-queries';
import type { EmployeeListQuery, EmployeeRecordState } from '@/services/employee-service';
import { useAuth } from '@/shared/auth/use-auth';
import type { EmployeeStatus, EmploymentType } from '@/types/api';
import { Permission } from '@/types/permissions';
import { EMPLOYEE_STATUSES, EMPLOYMENT_TYPES, EMPLOYMENT_TYPE_LABELS, RECORD_STATES, RECORD_STATE_LABELS, STATUS_LABELS } from '../lib/labels';
import { activeFilterCount } from '../lib/list-query';

type FilterValues = Pick<EmployeeListQuery, 'name' | 'employeeNumber' | 'email' | 'jobTitle' | 'departmentId' | 'statuses' | 'employmentTypes' | 'recordState'>;

const CLEARED: FilterValues = {
  name: undefined,
  employeeNumber: undefined,
  email: undefined,
  jobTitle: undefined,
  departmentId: undefined,
  statuses: [],
  employmentTypes: [],
  recordState: 'active',
};

const TEXT_FILTERS = [
  { key: 'name', label: 'Name', placeholder: 'First, last or preferred name' },
  { key: 'employeeNumber', label: 'Employee ID', placeholder: 'e.g. 000042' },
  { key: 'email', label: 'Work e-mail', placeholder: 'e.g. @meridian.app' },
  { key: 'jobTitle', label: 'Designation', placeholder: 'e.g. Engineer' },
] as const;

function pick(query: EmployeeListQuery): FilterValues {
  return {
    name: query.name,
    employeeNumber: query.employeeNumber,
    email: query.email,
    jobTitle: query.jobTitle,
    departmentId: query.departmentId,
    statuses: query.statuses ?? [],
    employmentTypes: query.employmentTypes ?? [],
    recordState: query.recordState ?? 'active',
  };
}

function toggle<T>(values: T[] | undefined, value: T, include: boolean): T[] {
  const rest = (values ?? []).filter((item) => item !== value);

  return include ? [...rest, value] : rest;
}

interface EmployeeFiltersProps {
  query: EmployeeListQuery;
  onChange: (changes: Partial<EmployeeListQuery>) => void;
}

/**
 * Advanced filters in a side panel. Edits are applied together, so the
 * directory is not re-queried on every keystroke.
 */
export function EmployeeFilters({ query, onChange }: EmployeeFiltersProps) {
  const [open, setOpen] = useState(false);
  const [draft, setDraft] = useState<FilterValues>(() => pick(query));
  const { can } = useAuth();
  const canSeeDepartments = can(Permission.OrganisationView);
  const departments = useDepartments(canSeeDepartments && open);
  const count = activeFilterCount(query);
  const stateGroupId = useId();

  const openPanel = (next: boolean) => {
    if (next) setDraft(pick(query));
    setOpen(next);
  };

  const set = (changes: Partial<FilterValues>) => setDraft((current) => ({ ...current, ...changes }));

  const apply = () => {
    onChange(draft);
    setOpen(false);
  };

  return (
    <>
      <Button variant="outline" onClick={() => openPanel(true)} aria-haspopup="dialog">
        <SlidersHorizontal aria-hidden="true" />
        Filters
        {count > 0 && (
          <Badge className="ml-1" aria-label={`${count} applied`}>
            {count}
          </Badge>
        )}
      </Button>

      <Drawer
        open={open}
        onOpenChange={openPanel}
        title="Filter employees"
        description="Text filters match any part of the field, ignoring case."
        footer={
          <>
            <Button variant="outline" onClick={() => setDraft(CLEARED)}>
              Clear all
            </Button>
            <Button onClick={apply}>Apply filters</Button>
          </>
        }
      >
        <form
          className="grid gap-5"
          onSubmit={(event) => {
            event.preventDefault();
            apply();
          }}
        >
          {TEXT_FILTERS.map(({ key, label, placeholder }) => (
            <FormField key={key} label={label}>
              <Input value={draft[key] ?? ''} placeholder={placeholder} maxLength={100} onChange={(event) => set({ [key]: event.target.value || undefined })} />
            </FormField>
          ))}

          {canSeeDepartments && (
            <FormField label="Department">
              <Select value={draft.departmentId ?? ''} onChange={(event) => set({ departmentId: event.target.value || undefined })} disabled={departments.isPending}>
                <option value="">All departments</option>
                {departments.data?.departments.map((department) => (
                  <option key={department.id} value={department.id}>
                    {department.name}
                  </option>
                ))}
              </Select>
            </FormField>
          )}

          <CheckboxGroup legend="Employment status">
            {EMPLOYEE_STATUSES.map((status: EmployeeStatus) => (
              <CheckboxOption
                key={status}
                label={STATUS_LABELS[status]}
                checked={draft.statuses?.includes(status) ?? false}
                onChange={(checked) => set({ statuses: toggle(draft.statuses, status, checked) })}
              />
            ))}
          </CheckboxGroup>

          <CheckboxGroup legend="Employment type">
            {EMPLOYMENT_TYPES.map((type: EmploymentType) => (
              <CheckboxOption
                key={type}
                label={EMPLOYMENT_TYPE_LABELS[type]}
                checked={draft.employmentTypes?.includes(type) ?? false}
                onChange={(checked) => set({ employmentTypes: toggle(draft.employmentTypes, type, checked) })}
              />
            ))}
          </CheckboxGroup>

          {can(Permission.EmployeeDelete) && (
            <fieldset className="grid gap-2">
              <legend className="mb-1 text-sm font-medium">Records</legend>
              {RECORD_STATES.map((state: EmployeeRecordState) => (
                <label key={state} className="flex items-center gap-2 text-sm">
                  <input
                    type="radio"
                    name={stateGroupId}
                    value={state}
                    checked={draft.recordState === state}
                    onChange={() => set({ recordState: state })}
                    className="size-4 accent-primary"
                  />
                  {RECORD_STATE_LABELS[state]}
                </label>
              ))}
            </fieldset>
          )}
          <button type="submit" hidden />
        </form>
      </Drawer>
    </>
  );
}

function CheckboxGroup({ legend, children }: { legend: string; children: ReactNode }) {
  return (
    <fieldset className="grid gap-2">
      <legend className="mb-1 text-sm font-medium">{legend}</legend>
      <div className="grid grid-cols-2 gap-2">{children}</div>
    </fieldset>
  );
}

function CheckboxOption({ label, checked, onChange }: { label: string; checked: boolean; onChange: (checked: boolean) => void }) {
  const id = useId();

  return (
    <div className="flex items-center gap-2">
      <Checkbox id={id} checked={checked} onCheckedChange={(value) => onChange(value === true)} />
      <Label htmlFor={id} className="font-normal">
        {label}
      </Label>
    </div>
  );
}

interface ActiveFiltersProps extends EmployeeFiltersProps {
  departmentName: (id: string) => string | undefined;
}

/** The applied filters as removable chips, so the current view is always explained. */
export function ActiveFilters({ query, onChange, departmentName }: ActiveFiltersProps) {
  const chips: { key: string; label: string; clear: Partial<EmployeeListQuery> }[] = [
    ...TEXT_FILTERS.filter(({ key }) => query[key]).map(({ key, label }) => ({ key, label: `${label}: ${query[key] ?? ''}`, clear: { [key]: undefined } })),
    ...(query.departmentId ? [{ key: 'department', label: `Department: ${departmentName(query.departmentId) ?? '…'}`, clear: { departmentId: undefined } }] : []),
    ...(query.statuses ?? []).map((status) => ({ key: `status-${status}`, label: STATUS_LABELS[status], clear: { statuses: query.statuses?.filter((item) => item !== status) } })),
    ...(query.employmentTypes ?? []).map((type) => ({
      key: `type-${type}`,
      label: EMPLOYMENT_TYPE_LABELS[type],
      clear: { employmentTypes: query.employmentTypes?.filter((item) => item !== type) },
    })),
    ...(query.recordState && query.recordState !== 'active' ? [{ key: 'state', label: RECORD_STATE_LABELS[query.recordState], clear: { recordState: 'active' as const } }] : []),
  ];

  if (chips.length === 0) return null;

  return (
    <ul className="flex flex-wrap items-center gap-2" aria-label="Applied filters">
      {chips.map((chip) => (
        <li key={chip.key}>
          <Badge variant="secondary" className="gap-1 py-1 pr-1">
            {chip.label}
            <button type="button" onClick={() => onChange(chip.clear)} className="rounded-full p-0.5 hover:bg-background" aria-label={`Remove filter ${chip.label}`}>
              <X className="size-3" aria-hidden="true" />
            </button>
          </Badge>
        </li>
      ))}
      <li>
        <Button variant="link" size="sm" onClick={() => onChange(CLEARED)}>
          Clear all
        </Button>
      </li>
    </ul>
  );
}
