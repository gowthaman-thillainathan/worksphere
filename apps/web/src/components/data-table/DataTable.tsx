import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import type { ReactNode } from 'react';
import { Checkbox } from '../ui/checkbox';
import { Skeleton } from '../ui/skeleton';
import { cn } from '@/shared/lib/utils';

export type SortDirection = 'asc' | 'desc';

export interface SortState<Field extends string = string> {
  field: Field;
  direction: SortDirection;
}

export interface DataTableColumn<Row, Field extends string = string> {
  id: string;
  header: string;
  cell: (row: Row) => ReactNode;
  /** When set, the header becomes a sort button for this API field. */
  sortField?: Field;
  className?: string;
  /** Hide on small screens to keep the table readable on mobile. */
  hideBelow?: 'sm' | 'md' | 'lg';
}

/** Row selection with checkboxes; the header checkbox selects every visible row. */
export interface DataTableSelection<Row> {
  isSelected: (row: Row) => boolean;
  onToggle: (row: Row, selected: boolean) => void;
  onToggleAll: (rows: Row[], selected: boolean) => void;
  /** Accessible name of a row's checkbox, e.g. "Select Nimal Perera". */
  rowLabel: (row: Row) => string;
}

interface DataTableProps<Row, Field extends string> {
  /** Announced to screen readers as the table caption. */
  caption: string;
  columns: DataTableColumn<Row, Field>[];
  rows: Row[] | undefined;
  getRowId: (row: Row) => string;
  isLoading?: boolean;
  sort?: SortState<Field>;
  onSortChange?: (sort: SortState<Field>) => void;
  empty: ReactNode;
  loadingRows?: number;
  selection?: DataTableSelection<Row>;
}

const HIDE_BELOW = { sm: 'hidden sm:table-cell', md: 'hidden md:table-cell', lg: 'hidden lg:table-cell' } as const;

function ariaSort(field: string | undefined, sort: SortState | undefined): 'ascending' | 'descending' | 'none' | undefined {
  if (!field) return undefined;
  if (sort?.field !== field) return 'none';

  return sort.direction === 'asc' ? 'ascending' : 'descending';
}

/**
 * Generic table for API collections: server-side sorting through header
 * buttons (aria-sort), optional row selection, skeleton rows while loading,
 * and an empty state.
 */
export function DataTable<Row, Field extends string = string>({
  caption,
  columns,
  rows,
  getRowId,
  isLoading,
  sort,
  onSortChange,
  empty,
  loadingRows = 5,
  selection,
}: DataTableProps<Row, Field>) {
  const visibleRows = isLoading ? [] : (rows ?? []);
  const selectedCount = selection ? visibleRows.filter((row) => selection.isSelected(row)).length : 0;
  const allSelected = visibleRows.length > 0 && selectedCount === visibleRows.length;
  const columnCount = columns.length + (selection ? 1 : 0);

  const toggleSort = (field: Field) => {
    const direction: SortDirection = sort?.field === field && sort.direction === 'asc' ? 'desc' : 'asc';
    onSortChange?.({ field, direction });
  };

  return (
    <div className="overflow-x-auto rounded-xl border bg-card" aria-busy={isLoading || undefined}>
      <table className="w-full caption-bottom text-sm">
        <caption className="sr-only">{caption}</caption>
        <thead className="border-b bg-muted/40">
          <tr>
            {selection && (
              <th scope="col" className="h-10 w-10 px-4 align-middle">
                <Checkbox
                  aria-label="Select all rows on this page"
                  checked={allSelected ? true : selectedCount > 0 ? 'indeterminate' : false}
                  disabled={visibleRows.length === 0}
                  onCheckedChange={(checked) => selection.onToggleAll(visibleRows, checked === true)}
                />
              </th>
            )}
            {columns.map((column) => (
              <th
                key={column.id}
                scope="col"
                aria-sort={ariaSort(column.sortField, sort)}
                className={cn('h-10 px-4 text-left align-middle font-medium text-muted-foreground', column.hideBelow && HIDE_BELOW[column.hideBelow], column.className)}
              >
                {column.sortField && onSortChange ? (
                  <button type="button" onClick={() => toggleSort(column.sortField as Field)} className="-ml-2 inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-accent hover:text-accent-foreground">
                    {column.header}
                    <SortIcon active={sort?.field === column.sortField} direction={sort?.direction} />
                  </button>
                ) : (
                  column.header
                )}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {isLoading &&
            Array.from({ length: loadingRows }, (_, index) => (
              <tr key={`loading-${index}`} className="border-b last:border-0">
                {selection && <td className="w-10 px-4 py-3" />}
                {columns.map((column) => (
                  <td key={column.id} className={cn('px-4 py-3', column.hideBelow && HIDE_BELOW[column.hideBelow])}>
                    <Skeleton className="h-4 w-full max-w-40" />
                  </td>
                ))}
              </tr>
            ))}
          {!isLoading &&
            rows?.map((row) => {
              const selected = selection?.isSelected(row) ?? false;

              return (
                <tr
                  key={getRowId(row)}
                  aria-selected={selection ? selected : undefined}
                  className={cn('border-b transition-colors last:border-0 hover:bg-muted/40', selected && 'bg-primary/5')}
                >
                  {selection && (
                    <td className="w-10 px-4 py-3 align-middle">
                      <Checkbox aria-label={selection.rowLabel(row)} checked={selected} onCheckedChange={(checked) => selection.onToggle(row, checked === true)} />
                    </td>
                  )}
                  {columns.map((column) => (
                    <td key={column.id} className={cn('px-4 py-3 align-middle', column.hideBelow && HIDE_BELOW[column.hideBelow], column.className)}>
                      {column.cell(row)}
                    </td>
                  ))}
                </tr>
              );
            })}
          {!isLoading && rows?.length === 0 && (
            <tr>
              <td colSpan={columnCount}>{empty}</td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}

function SortIcon({ active, direction }: { active: boolean; direction: SortDirection | undefined }) {
  if (!active) return <ArrowUpDown className="size-3.5 opacity-50" aria-hidden="true" />;

  return direction === 'asc' ? <ArrowUp className="size-3.5" aria-hidden="true" /> : <ArrowDown className="size-3.5" aria-hidden="true" />;
}
