import { useId, useRef, type KeyboardEvent, type ReactNode } from 'react';
import { cn } from '@/shared/lib/utils';

export interface TabItem<Key extends string> {
  key: Key;
  label: ReactNode;
  content: ReactNode;
}

interface TabsProps<Key extends string> {
  label: string;
  tabs: TabItem<Key>[];
  value: Key;
  onValueChange: (key: Key) => void;
  className?: string;
}

const NEXT_KEYS = new Set(['ArrowRight', 'ArrowDown']);
const PREVIOUS_KEYS = new Set(['ArrowLeft', 'ArrowUp']);

/**
 * WAI-ARIA tabs with automatic activation: arrow keys move between tabs,
 * Home/End jump to the first/last, and only the selected tab is in the tab
 * order. Controlled, so the selection can live in the URL.
 */
export function Tabs<Key extends string>({ label, tabs, value, onValueChange, className }: TabsProps<Key>) {
  const baseId = useId();
  const buttons = useRef(new Map<Key, HTMLButtonElement>());
  const index = Math.max(tabs.findIndex((tab) => tab.key === value), 0);
  const selected = tabs[index];

  const focusTab = (target: number) => {
    const tab = tabs[(target + tabs.length) % tabs.length];

    if (!tab) return;
    onValueChange(tab.key);
    buttons.current.get(tab.key)?.focus();
  };

  const onKeyDown = (event: KeyboardEvent) => {
    if (NEXT_KEYS.has(event.key)) focusTab(index + 1);
    else if (PREVIOUS_KEYS.has(event.key)) focusTab(index - 1);
    else if (event.key === 'Home') focusTab(0);
    else if (event.key === 'End') focusTab(tabs.length - 1);
    else return;

    event.preventDefault();
  };

  return (
    <div className={cn('grid gap-4', className)}>
      <div role="tablist" aria-label={label} className="flex gap-1 overflow-x-auto border-b">
        {tabs.map((tab) => {
          const active = tab.key === selected?.key;

          return (
            <button
              key={tab.key}
              ref={(element) => {
                if (element) buttons.current.set(tab.key, element);
                else buttons.current.delete(tab.key);
              }}
              type="button"
              role="tab"
              id={`${baseId}-tab-${tab.key}`}
              aria-selected={active}
              aria-controls={`${baseId}-panel-${tab.key}`}
              tabIndex={active ? 0 : -1}
              onClick={() => onValueChange(tab.key)}
              onKeyDown={onKeyDown}
              className={cn(
                '-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                active ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground',
              )}
            >
              {tab.label}
            </button>
          );
        })}
      </div>
      {selected && (
        <div role="tabpanel" id={`${baseId}-panel-${selected.key}`} aria-labelledby={`${baseId}-tab-${selected.key}`} tabIndex={0} className="outline-none">
          {selected.content}
        </div>
      )}
    </div>
  );
}
