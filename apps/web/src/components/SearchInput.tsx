import { Search, X } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { cn } from '@/shared/lib/utils';
import { Input } from './ui/input';

interface SearchInputProps {
  value: string;
  onSearch: (value: string) => void;
  label: string;
  placeholder?: string;
  debounceMs?: number;
  className?: string;
}

/** Debounced search box; the label is visually hidden but announced. */
export function SearchInput({ value, onSearch, label, placeholder, debounceMs = 300, className }: SearchInputProps) {
  const id = useId();
  const [draft, setDraft] = useState(value);
  const [syncedValue, setSyncedValue] = useState(value);
  const debounced = useDebouncedValue(draft, debounceMs);

  // Follow external changes (e.g. back button) by adjusting state during render.
  if (value !== syncedValue) {
    setSyncedValue(value);
    setDraft(value);
  }

  useEffect(() => {
    if (debounced !== value) onSearch(debounced);
  }, [debounced, value, onSearch]);

  return (
    <div className={cn('relative', className)} role="search">
      <label htmlFor={id} className="sr-only">
        {label}
      </label>
      <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
      <Input id={id} type="search" value={draft} placeholder={placeholder ?? label} onChange={(event) => setDraft(event.target.value)} className="pl-9 pr-9" />
      {draft && (
        <button
          type="button"
          onClick={() => {
            setDraft('');
            onSearch('');
          }}
          className="absolute right-2 top-1/2 -translate-y-1/2 rounded-sm p-1 text-muted-foreground hover:text-foreground"
          aria-label="Clear search"
        >
          <X className="size-4" aria-hidden="true" />
        </button>
      )}
    </div>
  );
}
