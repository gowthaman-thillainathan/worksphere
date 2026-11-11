import { Building2, LayoutDashboard, Settings, ShieldCheck, Users, type LucideIcon } from 'lucide-react';
import { paths } from '@/routes/paths';
import { Permission, type PermissionKey } from '@/types/permissions';

export interface NavigationItem {
  label: string;
  to: string;
  icon: LucideIcon;
  /** Shown only to users holding this permission (from /me). */
  permission?: PermissionKey;
}

export const navigation: NavigationItem[] = [
  { label: 'Dashboard', to: paths.dashboard, icon: LayoutDashboard },
  { label: 'Employees', to: paths.employees, icon: Users, permission: Permission.EmployeeView },
  { label: 'Departments', to: paths.departments, icon: Building2, permission: Permission.OrganisationView },
  { label: 'Roles', to: paths.roles, icon: ShieldCheck, permission: Permission.AccessManage },
  { label: 'Settings', to: paths.settings, icon: Settings },
];
