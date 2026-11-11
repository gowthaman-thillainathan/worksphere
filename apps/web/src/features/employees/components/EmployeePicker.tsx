import { X } from 'lucide-react';
import { useId, useState, type ComponentProps, type KeyboardEvent } from 'react';
import { Input } from '@/components/ui/input';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { cn } from '@/shared/lib/utils';
import { useEmployees } from '../api/employee-queries';

const RESULT_LIMIT = 8;

export interface PickedEmployee {
  id: string;
  label: string;
}

interface EmployeePickerProps extends Omit<ComponentProps<'input'>, 'value' | 'onChange'> {
  value: PickedEmployee | null;
  onChange: (value: PickedEmployee | null) => void;
  /** Employee to leave out of the results (e.g. the one being edited). */
  excludeId?: string;
}

/**
 * Accessible combobox (WAI-ARIA 1.2 pattern) searching the employee directory:
 * type to search, arrow keys to move, Enter to pick, Escape to close.
 */
export function EmployeePicker({ value, onChange, excludeId, className, onBlur, ...inputProps }: EmployeePickerProps) {
  const listboxId = useId();
  const [term, setTerm] = useState('');
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(0);
  const search = useDebouncedValue(term, 250);
  const query = { search, perPage: RESULT_LIMIT };

  const results = useEmployees(query, { enabled: open });
  const options = results.data?.data.filter((employee) => employee.id !== excludeId) ?? [];

  const pick = (index: number) => {
    const employee = options[index];

    if (employee) {
      onChange({ id: employee.id, label: employee.display_name });
      setTerm('');
      setOpen(false);
    }
  };

  const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setOpen(true);
      setActive((index) => Math.min(index + 1, options.length - 1));
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      setActive((index) => Math.max(index - 1, 0));
    } else if (event.key === 'Enter' && open) {
      event.preventDefault();
      pick(active);
    } else if (event.key === 'Escape') {
      setOpen(false);
    }
  };

  if (value) {
    return (
      <div className={cn('flex h-9 items-center justify-between rounded-md border border-input px-3 text-sm', className)}>
        <span className="truncate">{value.label}</span>
        <button type="button" onClick={() => onChange(null)} className="rounded-sm p-1 text-muted-foreground hover:text-foreground" aria-label={`Remove ${value.label}`}>
          <X className="size-4" aria-hidden="true" />
        </button>
      </div>
    );
  }

  return (
    <div className={cn('relative', className)}>
      <Input
        {...inputProps}
        role="combobox"
        aria-expanded={open}
        aria-controls={listboxId}
        aria-autocomplete="list"
        aria-activedescendant={open && options[active] ? `${listboxId}-${active}` : undefined}
        autoComplete="off"
        placeholder="Search by name or number"
        value={term}
        onChange={(event) => {
          setTerm(event.target.value);
          setActive(0);
          setOpen(true);
        }}
        onFocus={() => setOpen(true)}
        onBlur={(event) => {
          setOpen(false);
          onBlur?.(event);
        }}
        onKeyDown={onKeyDown}
      />
      {open && (
        <ul id={listboxId} role="listbox" className="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-md">
          {results.isFetching && options.length === 0 && <li className="px-2 py-1.5 text-sm text-muted-foreground">Searching…</li>}
          {!results.isFetching && options.length === 0 && <li className="px-2 py-1.5 text-sm text-muted-foreground">No employees found</li>}
          {options.map((employee, index) => (
            // Keyboard selection is handled on the combobox input (aria-activedescendant), per the WAI-ARIA pattern.
            // eslint-disable-next-line jsx-a11y/click-events-have-key-events
            <li
              key={employee.id}
              id={`${listboxId}-${index}`}
              role="option"
              aria-selected={index === active}
              onMouseDown={(event) => event.preventDefault()}
              onClick={() => pick(index)}
              className={cn('cursor-pointer rounded-sm px-2 py-1.5 text-sm', index === active && 'bg-accent text-accent-foreground')}
            >
              {employee.display_name} <span className="text-muted-foreground">· {employee.employee_number}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
