import { useQuery } from '@tanstack/react-query';
import { RoleService } from '@/services/role-service';
import { queryKeys } from '@/shared/lib/query-keys';

/** Roles change rarely: cached for a few minutes and shared by every screen. */
const ROLES_STALE_TIME = 5 * 60_000;

export function useRoles(enabled = true) {
  return useQuery({ queryKey: queryKeys.roles.all, queryFn: ({ signal }) => RoleService.roles(signal), staleTime: ROLES_STALE_TIME, enabled });
}

export function usePermissionCatalogue() {
  return useQuery({ queryKey: queryKeys.roles.permissions, queryFn: ({ signal }) => RoleService.permissions(signal), staleTime: ROLES_STALE_TIME });
}
