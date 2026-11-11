import { useCallback } from 'react';
import { usePersistentState } from '@/hooks/use-persistent-state';
import { COLUMN_PREFERENCES_KEY, DEFAULT_VISIBLE_COLUMNS, EMPLOYEE_COLUMN_IDS, isColumnList, type EmployeeColumnId } from '../lib/table-columns';

/** Which optional directory columns are shown, remembered in localStorage. */
export function useColumnPreferences() {
  const [visible, setVisible] = usePersistentState<EmployeeColumnId[]>(COLUMN_PREFERENCES_KEY, DEFAULT_VISIBLE_COLUMNS, isColumnList);

  const toggle = useCallback(
    (column: EmployeeColumnId, show: boolean) =>
      // Keep the canonical column order whatever the order of clicks.
      setVisible(EMPLOYEE_COLUMN_IDS.filter((id) => (id === column ? show : visible.includes(id)))),
    [visible, setVisible],
  );

  const reset = useCallback(() => setVisible(DEFAULT_VISIBLE_COLUMNS), [setVisible]);

  return { visible, toggle, reset };
}
