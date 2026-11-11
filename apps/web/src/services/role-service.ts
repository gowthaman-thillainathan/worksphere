import { listPermissions, listRoles } from './api/generated/endpoints/access-control/access-control';
import type { Permission, Role } from './api/generated/model';

export const RoleService = {
  async roles(signal?: AbortSignal): Promise<Role[]> {
    return (await listRoles({ signal })).data;
  },

  async permissions(signal?: AbortSignal): Promise<Permission[]> {
    return (await listPermissions({ signal })).data;
  },
};
