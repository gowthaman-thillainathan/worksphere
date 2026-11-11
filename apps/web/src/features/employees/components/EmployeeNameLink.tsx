import { Link } from 'react-router';
import { paths } from '@/routes/paths';
import type { Employee } from '@/types/api';
import { usePrefetchEmployee } from '../api/employee-queries';

/** The employee's name linking to their profile; the profile is prefetched on hover or focus. */
export function EmployeeNameLink({ employee }: { employee: Employee }) {
  const prefetch = usePrefetchEmployee();

  if (employee.deleted_at !== null) {
    return <span className="font-medium text-muted-foreground line-through decoration-1">{employee.display_name}</span>;
  }

  return (
    <Link
      to={paths.employee(employee.id)}
      className="font-medium hover:underline"
      onMouseEnter={() => prefetch(employee.id)}
      onFocus={() => prefetch(employee.id)}
    >
      {employee.display_name}
    </Link>
  );
}
