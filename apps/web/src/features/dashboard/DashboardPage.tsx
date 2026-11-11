import { Building2, Users } from 'lucide-react';
import { PageHeader } from '@/components/PageHeader';
import { useDepartments } from '@/features/departments/department-queries';
import { useHeadcount } from '@/features/employees/api/employee-queries';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { useAuth, useCurrentUser } from '@/shared/auth/use-auth';
import { formatNumber } from '@/shared/lib/format';
import { Permission } from '@/types/permissions';
import { QuickActionsWidget } from './widgets/QuickActionsWidget';
import { RecentActivityWidget } from './widgets/RecentActivityWidget';
import { RecentEmployeesWidget } from './widgets/RecentEmployeesWidget';
import { StatCard } from './widgets/StatCard';

/** Each widget appears only for users allowed to see its data. */
export default function DashboardPage() {
  useDocumentTitle('Dashboard');
  const { can } = useAuth();
  const { user } = useCurrentUser();
  const canViewEmployees = can(Permission.EmployeeView);
  const canViewDepartments = can(Permission.OrganisationView);
  const headcount = useHeadcount(canViewEmployees);
  const departments = useDepartments(canViewDepartments);
  const onLeave = headcount.data?.by_status.on_leave ?? 0;

  return (
    <>
      <PageHeader title={`Welcome back, ${user.name.split(' ')[0] ?? user.name}`} description="Here is what is happening in your organisation." />

      {(canViewEmployees || canViewDepartments) && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {canViewEmployees && (
            <StatCard
              label="Employees"
              icon={Users}
              value={headcount.data?.total}
              isLoading={headcount.isPending}
              error={headcount.error}
              detail={headcount.data ? `${formatNumber(onLeave)} on leave` : undefined}
              to={paths.employees}
            />
          )}
          {canViewDepartments && (
            <StatCard label="Departments" icon={Building2} value={departments.data?.total} isLoading={departments.isPending} error={departments.error} to={paths.departments} />
          )}
        </div>
      )}

      <div className="grid gap-6 lg:grid-cols-2">
        {canViewEmployees && <RecentEmployeesWidget />}
        <div className="space-y-6">
          <QuickActionsWidget />
          {can(Permission.AuditView) && <RecentActivityWidget />}
        </div>
      </div>
    </>
  );
}
