import { UserPlus } from 'lucide-react';
import { Link } from 'react-router';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useEmployees } from '@/features/employees/api/employee-queries';
import { EmployeeAvatar } from '@/features/employees/components/EmployeeAvatar';
import { EmployeeStatusBadge } from '@/features/employees/components/EmployeeStatusBadge';
import { paths } from '@/routes/paths';
import type { EmployeeListQuery } from '@/services/employee-service';
import { formatDate } from '@/shared/lib/format';

const RECENT: EmployeeListQuery = { sort: [{ field: 'created_at', direction: 'desc' }], perPage: 5 };

export function RecentEmployeesWidget() {
  const employees = useEmployees(RECENT);

  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between">
        <CardTitle>Recently added</CardTitle>
        <Button asChild variant="link" size="sm" className="h-auto p-0">
          <Link to={paths.employees}>View all</Link>
        </Button>
      </CardHeader>
      <CardContent>
        {employees.isPending && (
          <ul className="space-y-3" aria-busy="true">
            {Array.from({ length: 5 }, (_, index) => (
              <li key={index} className="flex items-center gap-3">
                <Skeleton className="size-8 rounded-full" />
                <Skeleton className="h-4 flex-1" />
              </li>
            ))}
          </ul>
        )}
        {employees.isError && <ErrorState error={employees.error} onRetry={() => employees.refetch()} className="py-6" />}
        {employees.data?.data.length === 0 && <EmptyState icon={UserPlus} title="No employees yet" className="py-6" />}
        {employees.data && employees.data.data.length > 0 && (
          <ul className="divide-y">
            {employees.data.data.map((employee) => (
              <li key={employee.id} className="flex items-center gap-3 py-2.5">
                <EmployeeAvatar employee={employee} />
                <div className="min-w-0 flex-1">
                  <Link to={paths.employee(employee.id)} className="block truncate text-sm font-medium hover:underline">
                    {employee.display_name}
                  </Link>
                  <p className="truncate text-xs text-muted-foreground">
                    {employee.job_title} · hired {formatDate(employee.hire_date)}
                  </p>
                </div>
                <EmployeeStatusBadge status={employee.status} />
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  );
}
