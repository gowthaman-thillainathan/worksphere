import { SearchInput } from '@/components/SearchInput';

interface EmployeeSearchProps {
  value: string;
  onSearch: (search: string | undefined) => void;
  className?: string;
}

/** Global search across name, employee ID and work e-mail (debounced by SearchInput). */
export function EmployeeSearch({ value, onSearch, className }: EmployeeSearchProps) {
  return (
    <SearchInput
      label="Search employees"
      placeholder="Search by name, employee ID or e-mail"
      value={value}
      onSearch={(search) => onSearch(search || undefined)}
      className={className}
    />
  );
}
