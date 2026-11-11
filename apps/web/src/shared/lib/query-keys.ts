import type { AuditQuery } from '@/services/audit-service';
import type { EmployeeListQuery } from '@/services/employee-service';

/**
 * Hierarchical TanStack Query keys. Invalidating a prefix (e.g.
 * queryKeys.employees.all) refreshes everything below it.
 */
export const queryKeys = {
  me: ['me'] as const,
  employees: {
    all: ['employees'] as const,
    lists: () => [...queryKeys.employees.all, 'list'] as const,
    list: (query: EmployeeListQuery) => [...queryKeys.employees.lists(), query] as const,
    detail: (id: string) => [...queryKeys.employees.all, 'detail', id] as const,
    photo: (id: string, version: number) => [...queryKeys.employees.all, 'photo', id, version] as const,
    headcount: () => [...queryKeys.employees.all, 'headcount'] as const,
  },
  departments: {
    all: ['departments'] as const,
    list: () => [...queryKeys.departments.all, 'list'] as const,
  },
  roles: {
    all: ['roles'] as const,
    permissions: ['permissions'] as const,
  },
  audit: {
    all: ['audit'] as const,
    list: (query: AuditQuery) => [...queryKeys.audit.all, query] as const,
  },
};
