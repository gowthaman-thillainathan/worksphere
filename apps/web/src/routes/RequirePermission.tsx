import { Outlet } from 'react-router';
import { ForbiddenPage } from '@/features/errors/ForbiddenPage';
import { useAuth } from '@/shared/auth/use-auth';
import type { PermissionKey } from '@/types/permissions';

/**
 * Hides a section from users without the permission. This is about the UI
 * only: the API enforces the same permission on every request.
 */
export function RequirePermission({ permission }: { permission: PermissionKey }) {
  const { can } = useAuth();

  return can(permission) ? <Outlet /> : <ForbiddenPage />;
}
