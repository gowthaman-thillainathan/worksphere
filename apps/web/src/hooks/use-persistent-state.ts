import { useCallback, useState } from 'react';

/**
 * State remembered in localStorage between visits (per browser). Storage
 * can be unavailable or hold stale data from an older version, so reads are
 * validated and failures fall back to the default instead of breaking the page.
 */
export function usePersistentState<Value>(key: string, fallback: Value, isValid: (value: unknown) => value is Value) {
  const [value, setValue] = useState<Value>(() => {
    try {
      const stored = localStorage.getItem(key);
      const parsed: unknown = stored === null ? undefined : JSON.parse(stored);

      return isValid(parsed) ? parsed : fallback;
    } catch {
      return fallback;
    }
  });

  const update = useCallback(
    (next: Value) => {
      setValue(next);

      try {
        localStorage.setItem(key, JSON.stringify(next));
      } catch {
        // Private mode or full storage: the preference lasts for this visit only.
      }
    },
    [key],
  );

  return [value, update] as const;
}
