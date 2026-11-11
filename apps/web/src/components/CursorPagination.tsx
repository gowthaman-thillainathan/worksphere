import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useId } from 'react';
import { Button } from './ui/button';
import { Select } from './ui/select';

interface CursorPaginationProps {
  previousCursor: string | null | undefined;
  nextCursor: string | null | undefined;
  onNavigate: (cursor: string | undefined) => void;
  perPage: number;
  onPerPageChange: (perPage: number) => void;
  pageSizes?: readonly number[];
  disabled?: boolean;
}

const DEFAULT_PAGE_SIZES = [10, 25, 50, 100] as const;

/**
 * Previous/next navigation for cursor-paginated lists (no page numbers:
 * cursors are stable under concurrent inserts, see docs/05 §3).
 */
export function CursorPagination({ previousCursor, nextCursor, onNavigate, perPage, onPerPageChange, pageSizes = DEFAULT_PAGE_SIZES, disabled }: CursorPaginationProps) {
  const id = useId();

  return (
    <nav className="flex flex-col-reverse items-center justify-between gap-3 sm:flex-row" aria-label="Pagination">
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <label htmlFor={id}>Rows per page</label>
        <Select id={id} value={perPage} onChange={(event) => onPerPageChange(Number(event.target.value))} className="h-8 w-20" disabled={disabled}>
          {pageSizes.map((size) => (
            <option key={size} value={size}>
              {size}
            </option>
          ))}
        </Select>
      </div>
      <div className="flex gap-2">
        <Button variant="outline" size="sm" onClick={() => onNavigate(previousCursor ?? undefined)} disabled={disabled || !previousCursor}>
          <ChevronLeft aria-hidden="true" />
          Previous
        </Button>
        <Button variant="outline" size="sm" onClick={() => onNavigate(nextCursor ?? undefined)} disabled={disabled || !nextCursor}>
          Next
          <ChevronRight aria-hidden="true" />
        </Button>
      </div>
    </nav>
  );
}
