import { Columns3 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { EMPLOYEE_COLUMNS, EMPLOYEE_COLUMN_IDS, type EmployeeColumnId } from '../lib/table-columns';

interface EmployeeColumnSelectorProps {
  visible: readonly EmployeeColumnId[];
  onToggle: (column: EmployeeColumnId, show: boolean) => void;
  onReset: () => void;
}

/** Show or hide directory columns; the menu stays open while toggling. */
export function EmployeeColumnSelector({ visible, onToggle, onReset }: EmployeeColumnSelectorProps) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" className="hidden md:inline-flex">
          <Columns3 aria-hidden="true" />
          Columns
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent>
        <DropdownMenuLabel>Show columns</DropdownMenuLabel>
        <DropdownMenuSeparator />
        {EMPLOYEE_COLUMN_IDS.map((column) => (
          <DropdownMenuCheckboxItem
            key={column}
            checked={visible.includes(column)}
            onCheckedChange={(checked) => onToggle(column, checked)}
            onSelect={(event) => event.preventDefault()}
          >
            {EMPLOYEE_COLUMNS[column].label}
          </DropdownMenuCheckboxItem>
        ))}
        <DropdownMenuSeparator />
        <DropdownMenuItem onSelect={onReset}>Reset to default</DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
