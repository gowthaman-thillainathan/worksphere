import { useCallback, useMemo, useState } from 'react';
import type { Employee } from '@/types/api';

/**
 * Selected employees, kept across pages of the same result set (by id, with
 * the record as last seen for bulk actions). Changing the filters clears
 * the selection, so actions never apply to rows the user can no longer see.
 */
export function useEmployeeSelection(resultSetKey: string) {
  const [selected, setSelected] = useState<ReadonlyMap<string, Employee>>(new Map());
  const [selectedFor, setSelectedFor] = useState(resultSetKey);

  // A different result set: start over (adjusting state during render, no extra effect pass).
  if (selectedFor !== resultSetKey) {
    setSelectedFor(resultSetKey);
    setSelected(new Map());
  }

  const toggle = useCallback((employee: Employee, include: boolean) => {
    setSelected((current) => {
      const next = new Map(current);

      if (include) next.set(employee.id, employee);
      else next.delete(employee.id);

      return next;
    });
  }, []);

  const toggleMany = useCallback((employees: Employee[], include: boolean) => {
    setSelected((current) => {
      const next = new Map(current);
      employees.forEach((employee) => (include ? next.set(employee.id, employee) : next.delete(employee.id)));

      return next;
    });
  }, []);

  const clear = useCallback(() => setSelected(new Map()), []);
  const isSelected = useCallback((employee: Employee) => selected.has(employee.id), [selected]);
  const employees = useMemo(() => [...selected.values()], [selected]);

  return { employees, count: selected.size, isSelected, toggle, toggleMany, clear };
}

export type EmployeeSelection = ReturnType<typeof useEmployeeSelection>;
