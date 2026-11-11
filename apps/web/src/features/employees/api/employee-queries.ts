import { keepPreviousData, useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query';
import { AuditService } from '@/services/audit-service';
import { EmployeeService, type EmployeeListQuery } from '@/services/employee-service';
import { queryKeys } from '@/shared/lib/query-keys';
import type { Employee, EmployeeCreate, EmployeeUpdate, ListEmployees200 } from '@/types/api';

const AUDIT_PAGE_SIZE = 20;

export function useEmployees(query: EmployeeListQuery) {
  return useQuery({
    queryKey: queryKeys.employees.list(query),
    queryFn: ({ signal }) => EmployeeService.list(query, signal),
    placeholderData: keepPreviousData, // keep the table steady while the next page loads
  });
}

export function useEmployee(id: string) {
  return useQuery({
    queryKey: queryKeys.employees.detail(id),
    queryFn: ({ signal }) => EmployeeService.get(id, signal),
  });
}

export function useHeadcount(enabled = true) {
  return useQuery({
    queryKey: queryKeys.employees.headcount(),
    queryFn: ({ signal }) => EmployeeService.headcount(signal),
    enabled,
  });
}

/** Photos need the bearer token, so they are fetched as blobs (not <img src>). */
export function useEmployeePhoto(employee: Pick<Employee, 'id' | 'photo_url' | 'meta'>) {
  return useQuery({
    queryKey: queryKeys.employees.photo(employee.id, employee.meta.version),
    queryFn: ({ signal }) => EmployeeService.photo(employee.id, signal),
    enabled: Boolean(employee.photo_url),
    staleTime: Infinity, // keyed by version: a new photo means a new key
  });
}

export function useEmployeeAuditTrail(id: string, enabled: boolean) {
  return useQuery({
    queryKey: queryKeys.audit.list({ subjectType: 'employee', subjectId: id, perPage: AUDIT_PAGE_SIZE }),
    queryFn: ({ signal }) => AuditService.list({ subjectType: 'employee', subjectId: id, perPage: AUDIT_PAGE_SIZE }, signal),
    enabled,
  });
}

function storeEmployee(queryClient: QueryClient, employee: Employee) {
  queryClient.setQueryData(queryKeys.employees.detail(employee.id), employee);
  void queryClient.invalidateQueries({ queryKey: queryKeys.employees.lists() });
  void queryClient.invalidateQueries({ queryKey: queryKeys.employees.headcount() });
  void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });
}

export function useCreateEmployee() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (input: EmployeeCreate) => EmployeeService.create(input),
    onSuccess: (employee) => storeEmployee(queryClient, employee),
  });
}

export function useUpdateEmployee(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ version, changes }: { version: number; changes: EmployeeUpdate }) => EmployeeService.update(id, version, changes),
    onSuccess: (employee) => storeEmployee(queryClient, employee),
  });
}

/**
 * Optimistically removes the employee from every cached list page, rolls
 * back if the API refuses (e.g. 409 direct reports, 412 stale version).
 */
function useOptimisticListRemoval<Variables>(mutationFn: (variables: Variables) => Promise<unknown>, idOf: (variables: Variables) => string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn,
    onMutate: async (variables) => {
      const id = idOf(variables);
      await queryClient.cancelQueries({ queryKey: queryKeys.employees.lists() });
      const snapshot = queryClient.getQueriesData<ListEmployees200>({ queryKey: queryKeys.employees.lists() });

      queryClient.setQueriesData<ListEmployees200>({ queryKey: queryKeys.employees.lists() }, (page) =>
        page ? { ...page, data: page.data.filter((employee) => employee.id !== id) } : page,
      );

      return { snapshot };
    },
    onError: (_error, _variables, context) => {
      context?.snapshot.forEach(([key, data]) => queryClient.setQueryData(key, data));
    },
    onSettled: (_data, _error, variables) => {
      void queryClient.invalidateQueries({ queryKey: queryKeys.employees.all });
      void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });
      queryClient.removeQueries({ queryKey: queryKeys.employees.detail(idOf(variables)), exact: true });
    },
  });
}

export function useDeleteEmployee() {
  return useOptimisticListRemoval(
    ({ id, version }: { id: string; version: number }) => EmployeeService.remove(id, version),
    ({ id }) => id,
  );
}

export function useRestoreEmployee() {
  return useOptimisticListRemoval((id: string) => EmployeeService.restore(id), (id) => id);
}

export function useUploadEmployeePhoto(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (photo: File) => EmployeeService.uploadPhoto(id, photo),
    onSuccess: (employee) => storeEmployee(queryClient, employee),
  });
}

export function useRemoveEmployeePhoto(employee: Employee) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => EmployeeService.removePhoto(employee.id),
    onSuccess: () => {
      queryClient.setQueryData<Employee>(queryKeys.employees.detail(employee.id), (current) =>
        current ? { ...current, photo_url: null, meta: { ...current.meta, version: current.meta.version + 1 } } : current,
      );
      void queryClient.invalidateQueries({ queryKey: queryKeys.employees.all });
      void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });
    },
  });
}
