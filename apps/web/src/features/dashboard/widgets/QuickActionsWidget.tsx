import { Building2, ShieldCheck, UserPlus, Users, type LucideIcon } from 'lucide-react';
import { Link } from 'react-router';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { paths } from '@/routes/paths';
import { useAuth } from '@/shared/auth/use-auth';
import { Permission, type PermissionKey } from '@/types/permissions';

interface QuickAction {
  label: string;
  description: string;
  to: string;
  icon: LucideIcon;
  permission: PermissionKey;
}

const ACTIONS: QuickAction[] = [
  { label: 'Add employee', description: 'Create a new employee record', to: paths.newEmployee, icon: UserPlus, permission: Permission.EmployeeCreate },
  { label: 'Browse directory', description: 'Search and filter employees', to: paths.employees, icon: Users, permission: Permission.EmployeeView },
  { label: 'Manage departments', description: 'Organise your structure', to: paths.departments, icon: Building2, permission: Permission.OrganisationManage },
  { label: 'Review roles', description: 'See who can do what', to: paths.roles, icon: ShieldCheck, permission: Permission.AccessManage },
];

export function QuickActionsWidget() {
  const { can } = useAuth();
  const actions = ACTIONS.filter((action) => can(action.permission));

  if (actions.length === 0) {
    return null;
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Quick actions</CardTitle>
      </CardHeader>
      <CardContent>
        <ul className="grid gap-2 sm:grid-cols-2">
          {actions.map(({ label, description, to, icon: Icon }) => (
            <li key={to}>
              <Link to={to} className="flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-accent">
                <Icon className="mt-0.5 size-4 text-primary" aria-hidden="true" />
                <span>
                  <span className="block text-sm font-medium">{label}</span>
                  <span className="block text-xs text-muted-foreground">{description}</span>
                </span>
              </Link>
            </li>
          ))}
        </ul>
      </CardContent>
    </Card>
  );
}
